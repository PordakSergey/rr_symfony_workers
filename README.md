# rr-symfony-workers

Symfony-бандл для запуска приложения под [RoadRunner](https://roadrunner.dev): HTTP, Jobs, gRPC
и Temporal-воркеры, KV-кэш и dispatcher фоновых задач через Messenger.

Требуется PHP 8.4, Symfony 7/8.

## Установка

```bash
composer require flyerclub/rr-symfony-workers
```

```php
// config/bundles.php
return [
    // ...
    Rr\Bundle\Workers\RrWorkersBundle::class => ['all' => true],
];
```

Подключите runtime бандла — именно он подхватывает `RR_MODE` и запускает нужный воркер
вместо обычного Symfony-рантайма:

```json
// composer.json
"extra": {
    "runtime": {
        "class": "Rr\\Bundle\\Workers\\Runtime\\Runtime"
    }
}
```

Либо через env: `APP_RUNTIME=Rr\Bundle\Workers\Runtime\Runtime`.

Если `RR_MODE` не задан (обычный CLI, `bin/console`), рантайм ведёт себя как стандартный
`SymfonyRuntime`.

## Как это работает

```
RoadRunner ──RR_MODE──> Runtime ──> Runner ──> WorkerStorage ──> нужный WorkerInterface::run()
```

`WorkerStorage` перебирает все сервисы с тегом `rr.worker` и берёт первый, чей
`supports(RR_MODE)` вернул `true`. Свой воркер = класс, реализующий `WorkerInterface`
(тег навешивается автоматически через `instanceof`).

| RR_MODE | Воркер | Что делает |
|---|---|---|
| `http` | `HttpWorker` | RR-запрос → `Symfony\Request` → kernel → `Symfony\Response`, стек middleware |
| `jobs` | `JobsWorker` | задача из RR-пайплайна → десериализация по `JobMapInterface` → Messenger bus |
| `grpc` | `GrpcWorker` | сервисы с тегом `roadrunner.grpc_service` в `Spiral\RoadRunner\GRPC\Server` |
| `temporal` | `TemporalWorker` | activity/workflow, см. [src/Temporal/README.md](src/Temporal/README.md) |

После каждого запроса/задачи вызывается `services_resetter`, чтобы состояние сервисов
не текло между итерациями.

## Конфигурация

```yaml
# config/packages/rr_workers.yaml
rr_workers:
  kv:
    storages: ['local', 'redis']   # имена storage из секции kv в .rr.yaml
  jobs:
    default_queue: default         # пайплайн по умолчанию для RrJobDispatcher
    queues:
      default: ~
      flights.storage: { priority: 10 }
  temporal:
    default_queue: taskQueue
    workers:
      taskQueue: ~
```

Полное описание секции `temporal` — в [src/Temporal/README.md](src/Temporal/README.md).

## HTTP

`.rr.yaml`:

```yaml
version: '3'
server:
  command: "php public/index.php"
http:
  address: 0.0.0.0:8080
  pool:
    num_workers: 4
```

Особенности реализации:

* `HttpFoundationWorker` + `ServerParser` собирают полноценный `$_SERVER`, загруженные файлы
  оборачиваются в `UploadedFile`, `Basic`-заголовок раскладывается в `PHP_AUTH_USER` / `PHP_AUTH_PW`;
* необработанное исключение отдаётся клиентом через `HtmlErrorRenderer` (в debug — полный трейс),
  после чего воркер останавливается и RoadRunner поднимает новый;
* `kernel->terminate()` вызывается после отправки ответа, так что отложенная работа
  (Messenger `dispatch_after_current_bus`, логи) не задерживает клиента.

### Middleware

Middleware — генератор: код до `yield` выполняется до kernel, после `yield` — после отправки ответа.

```php
final class TimingMiddleware implements MiddlewareInterface
{
    public function process(Request $request, HttpKernelInterface $next): \Iterator
    {
        $start = microtime(true);
        yield $next->handle($request);
        // сюда попадаем уже после respond()
    }
}
```

Стек собирается `MiddlewaresCompilerPass` из параметра контейнера `middlewares.default`.
По умолчанию там `DoctrineORMMiddleware` — пингует открытые DBAL-соединения перед запросом
и закрывает мёртвые, иначе долгоживущий воркер упадёт на `MySQL server has gone away`.

Чтобы добавить свой:

```yaml
parameters:
  middlewares.default:
    before: ['Rr\Bundle\Workers\Middlewares\DoctrineORMMiddleware', 'App\Middleware\TimingMiddleware']
    after: []
```

## Jobs

`.rr.yaml`:

```yaml
jobs:
  pipelines:
    default:
      driver: memory
      config: { priority: 10 }
  consume: ['default']
```

Воркер получает задачу, спрашивает у `JobMapInterface` класс команды по имени задачи,
десериализует payload и отправляет в Messenger. Дефолтный `JobsMapper` возвращает пустоту —
перекройте его:

```php
final class JobsMapper implements JobMapInterface
{
    public function getAll(): array { return [SendEmail::class => SendEmail::class]; }
    public function getByName(string $name): string { return $this->getAll()[$name] ?? throw new \RuntimeException($name); }
}
```

```yaml
services:
  Rr\Bundle\Workers\Jobs\Contracts\Services\Map\JobMapInterface: '@App\Jobs\JobsMapper'
```

Успешная обработка → `ack()`, исключение → `nack()` с текстом ошибки.

## Отправка задач

### Temporal — `JobDispatcherInterface`

Единственная реализация — `TemporalJobDispatcher`, задача идёт через workflow,
результат можно дождаться.

```php
public function __construct(private JobDispatcherInterface $jobs) {}

$this->jobs->dispatch(new SendEmail($id));
$result = $this->jobs->dispatch(new SendEmail($id), returnResult: true)->getResult();
$this->jobs->dispatchPool([new SendEmail(1), new SendEmail(2)], returnResult: true);
```

Очередь по умолчанию — `rr_workers.temporal.default_queue`.

### RR jobs — `RrJobDispatcher`

Отдельный сервис под очередь: кладёт задачу в пайплайн и возвращает её id.
Результата у задачи нет — её подхватит `JobsWorker` и отправит в Messenger.

```php
public function __construct(private RrJobDispatcher $queue) {}

$id  = $this->queue->push(new SendEmail($id));                     // default_queue
$id  = $this->queue->push(new SendEmail($id), 'flights.storage');  // конкретная очередь
$id  = $this->queue->push(new SendEmail($id), 'slow', (new Options())->withDelay(60));
$ids = $this->queue->pushMany([new SendEmail(1), new SendEmail(2)], 'flights.storage');
```

Очереди и их опции по умолчанию — как `temporal.workers`, только для пайплайнов:

```yaml
rr_workers:
  jobs:
    default_queue: default
    queues:
      default: ~                              # без опций
      flights.storage: { priority: 10 }
      slow: { delay: 60, priority: 1, auto_ack: false }
```

Сами пайплайны объявляются в `.rr.yaml` (`jobs.pipelines`), здесь — только дефолтные
опции задач. `Options` в `push()` перекрывают конфиг (`Options::mergeOptional`).
Очередь, которой нет в `queues`, работает без дефолтных опций.

Ошибка пуша бросает `JobsException`, задача не теряется молча.

## KV-кэш

Перечислите storage в `rr_workers.kv.storages` — бандл зарегистрирует адаптер
`cache.adapter.roadrunner.kv_<storage>`:

```yaml
framework:
  cache:
    pools:
      app.rr_cache:
        adapter: cache.adapter.roadrunner.kv_local
```

Требует включённой секции `rpc` в `.rr.yaml` (адрес берётся из `RR_RPC`).

## gRPC

Классы, реализующие `Spiral\RoadRunner\GRPC\ServiceInterface`, тегируются автоматически
(`roadrunner.grpc_service`); `GrpcStorageCompilerPass` находит их gRPC-интерфейсы и регистрирует
в `GrpcServiceStorage`. Дополнительной настройки не нужно, кроме секции `grpc` в `.rr.yaml`.

## События

`WorkerStartEvent` / `WorkerStopEvent` — старт и остановка HTTP-воркера,
`KernelRebootEvent` — заготовка под перезагрузку ядра.

## Известные особенности

* `JobDispatcherInterface` — только Temporal. RR-очередь инжектится классом
  `RrJobDispatcher`, у неё своё API (`push()` / `pushMany()`).
* `RrJobDispatcher` требует включённой секции `rpc` в `.rr.yaml`.
* `TEMPORAL_URL` нужен всегда, когда контейнер создаёт temporal-клиенты, даже если приложение
  работает только в HTTP-режиме.

## Структура

```
config/services.php              регистрация всех сервисов бандла
src/Runtime/                     Runtime + Runner, точка входа под RoadRunner
src/Workers/                     реализации воркеров по RR_MODE
src/Storage/                     выбор воркера по режиму, реестр gRPC-сервисов
src/RoadRunnerBridge/            RR-запрос <-> HttpFoundation
src/Handlers/, src/Middlewares/  HTTP-пайплайн
src/Jobs/                        RR jobs: маппер, обработчик, диспетчер
src/Temporal/                    Temporal (см. отдельный README)
src/Cache/                       KV-адаптер для symfony/cache
src/DependencyInjection/         конфигурация и compiler pass'ы
```

## Лицензия

MIT, см. [LICENSE](LICENSE).