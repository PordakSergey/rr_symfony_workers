# Temporal

Temporal-часть бандла: воркер, клиенты, dispatcher задач через Symfony Messenger и cron-расписания.

## Требования

* `temporal/sdk` (уже в зависимостях), запущенный Temporal server;
* RoadRunner с включённой секцией `temporal`;
* переменная окружения `TEMPORAL_URL` (например `temporal:7233`) — необязательна,
  по умолчанию `localhost:7233`.

```yaml
# .rr.yaml
temporal:
  address: "localhost:7233"
  activities:
    num_workers: 4
```

`TemporalWorker` поднимается автоматически, когда RoadRunner запускает воркер в режиме
`temporal` (`Environment\Mode::MODE_TEMPORAL`).

## Конфигурация

```yaml
# config/packages/rr_workers.yaml
rr_workers:
  temporal:
    default_queue: taskQueue      # очередь по умолчанию для dispatcher и cron
    activity:
      start_to_close_timeout: 180 # секунд на выполнение activity; в debug дефолт 3600
      maximum_attempts: 2         # включая первую попытку, 0 — без ограничения; в debug дефолт 1
      initial_interval: 3         # секунд до первого ретрая
    workers:
      taskQueue: ~                # всё, что помечено тегами, регистрируется в этой очереди
      heavy:
        max_concurrent_activities: 20
        max_concurrent_workflows: 20
        activity_pollers: 5
        workflow_pollers: 2
        activities: ['App\Temporal\Activity\ReportActivity']   # пусто = все
        workflows:  ['App\Temporal\Workflow\ReportWorkflow']   # пусто = все
```

Один процесс воркера обслуживает все очереди из `workers`: на каждый ключ создаётся
отдельный `newWorker()` со своими лимитами и своим набором activity/workflow.

После каждой activity вызывается `services_resetter`, поэтому состояние сервисов Symfony
не течёт между задачами.

## Регистрация своих activity и workflow

Регистрация — по DI-тегам, `TemporalStorage` собирает их в `tagged_iterator`:

```yaml
services:
  App\Temporal\Activity\ReportActivity:
    tags: ['temporal.activity']
  App\Temporal\Workflow\ReportWorkflow:
    tags: ['temporal.workflow']
```

Activity создаются через контейнер (можно инжектить любые сервисы), workflow —
регистрируются по типу, Temporal инстанцирует их сам, зависимости туда инжектить нельзя.

## Отправка задач

`JobDispatcherInterface` реализует только `TemporalJobDispatcher`.
RR-очередь — отдельный класс `RrJobDispatcher` со своим API.

```php
public function __construct(private JobDispatcherInterface $jobs) {}

// одна команда, fire-and-forget
$this->jobs->dispatch(new SendEmail($userId));

// дождаться результата
$result = $this->jobs->dispatch(new SendEmail($userId), returnResult: true)->getResult();

// пачка команд, параллельно внутри одного workflow
$responses = $this->jobs->dispatchPool([new SendEmail(1), new SendEmail(2)], returnResult: true);

// своя очередь и свой тег (тег идёт в workflow id: "report-68b3f1...")
$this->jobs->dispatch(new BuildReport(), tag: 'report', queue: 'heavy');
```

Команда нормализуется сериализатором, `MessengerWorkflow` запускает `MessengerActivity`,
которая денормализует её обратно и отправляет в Messenger bus (`HandleTrait`, синхронно).
Значит: команда должна быть нормализуемой (без замыканий, ресурсов, Doctrine-прокси),
а её handler — существовать в приложении, где работает temporal-воркер.

При `kernel.debug` (dev) дефолты другие: `start_to_close_timeout` — 3600, `maximum_attempts` — 1,
workflow стартуют с `maximumAttempts: 1`, а `dispatchPool` — с execution timeout 2 часа вместо 10 минут.
Остановка на брейкпоинте xDebug не ловит таймаут и не запускает вторую попытку.
Явные значения в конфиге сильнее этих дефолтов.

Дефолты activity: `start_to_close = 3 мин`, 2 попытки, начальный интервал 3 с — меняются
в `rr_workers.temporal.activity` и применяются к обоим messenger-workflow. Значения
проставляет `TemporalWorker::run()` в `MessengerActivityOptions` (статика: workflow
инстанцирует сам Temporal, контейнера внутри нет). Нужны разные опции на разные
команды — свой workflow со своими `ActivityOptions`.

`dispatchPool` с `returnResult: false` возвращает пустой массив: workflow только
запускается, результаты не собираются. Ошибка отдельной команды в пуле не валит весь пул —
она превращается в `['error' => 'message']`.

## Messenger transport

Вместо явного `JobDispatcherInterface` можно роутить сообщения в Temporal штатным Messenger.

### Настройка

```yaml
# config/packages/messenger.yaml
framework:
  messenger:
    transports:
      temporal: 'temporal://default'                 # очередь из rr_workers.temporal.default_queue
      temporal_heavy: 'temporal://heavy?tag=report'  # очередь heavy, workflow id "report-..."
    routing:
      App\Message\SendEmail: temporal
      App\Message\BuildReport: temporal_heavy
```

DSN: `temporal://<queue>?tag=<tag>`.

* `queue` — task queue; `default` или пусто (`temporal://`) — `rr_workers.temporal.default_queue`.
  Другая очередь должна быть объявлена в `rr_workers.temporal.workers`, иначе её никто не слушает.
* `tag` — префикс workflow id (по умолчанию `messenger`), удобно искать в Temporal UI.

Фабрика транспорта регистрируется автоматически (autoconfigure, тег `messenger.transport_factory`).

### Отправка

```php
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;

public function __construct(private MessageBusInterface $bus) {}

// сразу
$this->bus->dispatch(new SendEmail(42));

// с задержкой 5 минут (миллисекунды)
$this->bus->dispatch(new SendEmail(42), [new DelayStamp(300_000)]);
$this->bus->dispatch(new SendEmail(42), [DelayStamp::delayFor(new \DateInterval('PT5M'))]);

// workflow id запущенной задачи
$workflowId = $this->bus->dispatch(new SendEmail(42))
    ->last(TransportMessageIdStamp::class)?->getId();
```

### Что происходит

1. Транспорт вызывает `TemporalJobDispatcher::dispatch()` — стартует `MessengerWorkflow` в нужной очереди;
   `DelayStamp` уходит в `WorkflowOptions::withWorkflowStartDelay()`.
2. Temporal-воркер выполняет `MessengerActivity`: денормализует сообщение и синхронно вызывает handler.
   Envelope помечается `ReceivedStamp`, поэтому сообщение не уходит в транспорт повторно.
3. Упал handler — activity ретраится по `rr_workers.temporal.activity`.

`messenger:consume temporal` запускать не нужно — сообщения забирает temporal-воркер.

### Требования и ограничения

* Handler должен существовать в приложении, где работает temporal-воркер.
* Сообщение нормализуется Symfony Serializer: без замыканий, ресурсов, Doctrine-прокси (передавайте id).
* Только fire-and-forget. Нужен результат — `JobDispatcherInterface::dispatch(returnResult: true)`.
* `serializer`, `retry_strategy`, `failure_transport` транспорта Messenger не действуют:
  payload нормализует `TemporalJobDispatcher`, ретраями управляет Temporal.
* Задержка без Messenger — только через `TemporalJobDispatcher::dispatch(..., delayMs: 5000)`,
  в `JobDispatcherInterface` параметра нет.

### Что выбрать

| Нужно | Чем |
|---|---|
| Fire-and-forget, обычный роутинг Messenger | транспорт `temporal://` |
| Дождаться результата | `JobDispatcherInterface::dispatch(returnResult: true)` |
| Пачка команд параллельно | `JobDispatcherInterface::dispatchPool()` |
| По расписанию | `CronMap` + `temporal:schedule:upsert` |

## Cron / расписания

Реализуйте `CronMapInterface` и перекройте дефолтный `CronMap` (он возвращает пустой список):

```php
final class CronMap implements CronMapInterface
{
    public function getAll(): array
    {
        return [
            new CronJob('nightly_report', '0 3 * * *', new BuildReport()),
            new CronJob('cleanup', '*/15 * * * *', new Cleanup(), taskQueue: 'heavy'),
            new CronJob('sync_prices', '0 * * * *', new SyncPrices(), envs: ['prod', 'stage']),
        ];
    }
}
```

```yaml
services:
  Rr\Bundle\Workers\Temporal\Contracts\Services\Cron\CronMapInterface:
    class: App\Temporal\CronMap
```

Синхронизация расписаний в Temporal:

```bash
php bin/console temporal:schedule:upsert
```

Команда идемпотентна: создаёт отсутствующие расписания, обновляет существующие и **удаляет**
те, чей id начинается с `cron_job_`, но которых больше нет в `CronMap`. Запускайте её при
деплое.

`envs` — список `APP_ENV` (`%kernel.environment%`), в которых задача активна; пустой (по умолчанию) —
во всех. В остальных окружениях команда пропускает задачу, а уже созданное расписание удаляет.

Идентификатор расписания — `cron_job_` + `getTaskId()`, он же префикс workflow id запусков
(Temporal дописывает время: `cron_job_nightly_report-2026-09-26T03:00:00Z`). Таймзона у `CronJob` жёстко `UTC` —
нужна другая, реализуйте `CronJobInterface` сам.

Расписание всегда стартует workflow-метод `run` (то есть `MessengerWorkflow`);
`CronJobInterface::getWorkflowType()` командой не используется.

## Что где лежит

| Путь | Зачем |
|---|---|
| `Factories/` | `ServiceClient`, `WorkflowClient`, `ScheduleClient` из `TEMPORAL_URL` (по умолчанию `localhost:7233`) |
| `Services/Storage/TemporalStorage.php` | собирает activity/workflow по тегам |
| `Services/Workflows/` | `MessengerWorkflow` (одна команда), `MessengerPoolWorkflow` (пачка) |
| `Services/Activities/MessengerActivity.php` | денормализация + Messenger bus |
| `Services/JobsDispatcher/` | `TemporalJobDispatcher` — точка входа для приложения |
| `SymfonyIntegration/Messenger/` | Messenger-транспорт `temporal://` и его фабрика |
| `Services/Cron/` | `CronJob`, дефолтный пустой `CronMap` |
| `Commands/` | `temporal:schedule:upsert` |
| `../Workers/TemporalWorker.php` | сам воркер, регистрация очередей |