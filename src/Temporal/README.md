# Temporal

Temporal-часть бандла: воркер, клиенты, dispatcher задач через Symfony Messenger и cron-расписания.

## Требования

* `temporal/sdk` (уже в зависимостях), запущенный Temporal server;
* RoadRunner с включённой секцией `temporal`;
* переменная окружения `TEMPORAL_URL` (например `localhost:7233`). Без неё
  `TemporalClientServiceFactory` бросит `BadConfigurationException`.

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

Дефолты activity: `start_to_close = 3 мин`, 2 попытки, начальный интервал 3 с.
Нужны другие — свой workflow с другими `ActivityOptions`.

`dispatchPool` с `returnResult: false` возвращает пустой массив: workflow только
запускается, результаты не собираются. Ошибка отдельной команды в пуле не валит весь пул —
она превращается в `['error' => 'message']`.

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

Идентификатор расписания — `cron_job_` + `getTaskId()`. Таймзона у `CronJob` жёстко `UTC` —
нужна другая, реализуйте `CronJobInterface` сам.

Расписание всегда стартует workflow-метод `run` (то есть `MessengerWorkflow`);
`CronJobInterface::getWorkflowType()` командой не используется.

## Что где лежит

| Путь | Зачем |
|---|---|
| `Factories/` | `ServiceClient`, `WorkflowClient`, `ScheduleClient` из `TEMPORAL_URL` |
| `Services/Storage/TemporalStorage.php` | собирает activity/workflow по тегам |
| `Services/Workflows/` | `MessengerWorkflow` (одна команда), `MessengerPoolWorkflow` (пачка) |
| `Services/Activities/MessengerActivity.php` | денормализация + Messenger bus |
| `Services/JobsDispatcher/` | `TemporalJobDispatcher` — точка входа для приложения |
| `Services/Cron/` | `CronJob`, дефолтный пустой `CronMap` |
| `Commands/` | `temporal:schedule:upsert` |
| `../Workers/TemporalWorker.php` | сам воркер, регистрация очередей |