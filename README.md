<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Тестовое задание

**Шаг 1: Установка и настройка Laravel**

Для начала вам потребуется установить Laravel. Для этого вам понадобится Composer, который является менеджером зависимостей PHP. Если у вас его еще нет, вы можете скачать его с официального сайта.

После установки Composer вам нужно установить Laravel. Это можно сделать с помощью следующей команды:

```sh
composer global require laravel/installer
```

Затем вы можете создать новый проект Laravel с помощью команды:

```sh
laravel new patient-app
```

**Шаг 2: Создание модели и миграции**

Создайте модель Patient с помощью команды artisan:

```sh
php artisan make:model Patient --migration
```

Это создаст новую модель и файл миграции. В файле миграции добавьте следующие столбцы:

```sh
// database/migrations/xxxx_xx_xx_xxxxxx_create_patients_table.php

public function up()
{
    Schema::create('patients', function (Blueprint $table) {
        $table->id();
        $table->string('first_name');
        $table->string('last_name');
        $table->date('birthdate');
        $table->integer('age');
        $table->enum('age_type', ['day', 'month', 'year']);
        $table->timestamps();
    });
}
```

Затем выполните миграцию:

```sh
php artisan migrate
```

**Шаг 3: Создание контроллера и сервиса**

Создайте контроллер с помощью artisan:

```sh
php artisan make:controller PatientController
```

Затем создайте новый сервис в папке `app/Services`, назовите его `PatientService.php`.

- Код контроллера

```sh
namespace App\Http\Controllers;

use App\Services\PatientService;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    protected $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'birthdate' => 'required|date',
        ]);

        $patient = $this->patientService->create($validatedData);

        return response()->json($patient);
    }

    public function index()
    {
        $patients = $this->patientService->getCachedPatients();

        return response()->json($patients);
    }
}
```

- Код сервиса

```sh
namespace App\Services;

use App\Jobs\SendPatientToQueue;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PatientService
{
    public function create(array $data): Patient
    {
        $birthdate = Carbon::parse($data['birthdate']);
        $age = Carbon::now()->diff($birthdate);
        $ageType = $this->getAgeType($age);

        $patient = Patient::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'birthdate' => $birthdate,
            'age' => $age->{$ageType},
            'age_type' => $ageType,
        ]);

        $patientData = [
            'name' => $patient->first_name.' '.$patient->last_name,
            'birthdate' => $patient->birthdate->format('d.m.Y'),
            'age' => $patient->age.' '.$patient->age_type,
        ];

        Cache::put('patient_'.$patient->id, $patientData, 300);
        SendPatientToQueue::dispatch($patient);

        return $patient;
    }

    private function getAgeType($age): string
    {
        if ($age->y > 0) {
            return 'year';
        }

        if ($age->m > 0) {
            return 'month';
        }

        return 'day';
    }

    public function getCachedPatients()
    {
        return Cache::remember('patients', 5 * 60, function () {
            return $this->getAllPatients();
        });
    }

    public function getAllPatients(): array
    {
        $patients = Patient::all()->map(function ($patient) {
            return [
                'name' => $patient->first_name . ' ' . $patient->last_name,
                'birthdate' => $patient->birthdate->format('d.m.Y'),
                'age' => $patient->age . ' ' . $patient->age_type
            ];
        });

        return $patients->toArray();
    }
}
```

**Шаг 4: Создание очереди**

Создайте новую очередь с помощью artisan:

```sh
php artisan make:job SendPatientToQueue
```

- Код работы очереди

```sh
namespace App\Jobs;

use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPatientToQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $patient;

    public function __construct(Patient $patient)
    {
        $this->patient = $patient;
    }

    public function handle()
    {
        // Здесь вы можете добавить логику отправки пациента в очередь.
        // Привожу пример такого кода
        //                        |
        //                        V
        // Добавить use Illuminate\Support\Facades\Redis;
        /* $patientData = [
            'name' => $this->patient->first_name.' '.$this->patient->last_name,
            'birthdate' => $this->patient->birthdate->format('d.m.Y'),
            'age' => $this->patient->age.' '.$this->patient->age_type,
        ];

        Redis::rpush('patients', json_encode($patientData)); */
    }
}
```

**Шаг 5: Обновление файла маршрутов**

Обновите файл маршрутов, чтобы добавить маршруты для создания и получения пациентов:

```sh
// routes/web.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PatientController;

// Иные маршруты

Route::post('/patients', [PatientController::class, 'store']);
Route::get('/patients', [PatientController::class, 'index']);
```

**Шаг 6: Запуск приложения**

Теперь вы готовы запустить свое приложение! Используйте команду artisan serve:

```sh
php artisan serve
```

### Остальные пояснения к решению

Этот код следует паттерну Model-View-Controller (MVC).
- **Модель (Model)**: `Patient` является моделью и представляет собой объектную модель данных. В этом случае, `Patient` - это модель Eloquent, которая обеспечивает доступ к данным в базе данных.
- **Представление (View)**: хотя в этом коде нет явно указанных представлений (поскольку это простой пример API, и ответы формируются в формате JSON), они могут быть представлены ответами контроллеров.
- **Контроллер (Controller)**: `PatientController` - это контроллер, который принимает запросы, обрабатывает их с помощью служб и моделей, и возвращает ответы.

Также здесь представлен сервис `PatientService`, который является слоем бизнес-логики и помогает держать контроллеры "тонкими", т.е. без излишней бизнес-логики. Это соответствует принципу разделения ответственности и помогает улучшить чистоту и читаемость кода.

Помимо этого, был использован класс `SendPatientToQueue`, который представляет собой задачу для очереди. Это помогает асинхронно обрабатывать операции, которые могут занять много времени, вроде отправки данных в сторонние сервисы.

Таким образом, этот код хорошо структурирован и следует принципам паттерна MVC, а также дополнительным практикам, которые помогают повысить качество кода.

### Какие здесь практики, которые помогают повысить качество кода?

- **Слой сервисов**: Вместо того, чтобы помещать всю бизнес-логику в контроллер, выделение слоя сервисов помогает сократить объем кода в контроллерах и делает их более чистыми и управляемыми. Это также упрощает повторное использование кода и сокращает дублирование.
- **Разделение ответственности**: Слой сервисов, контроллеры и модели имеют четко определенные обязанности. Это облегчает поддержку и расширение кода в будущем, так как каждая часть системы занимается только своей областью.
- **Использование очередей**: Очереди помогают управлять операциями, которые могут занимать много времени для выполнения, делая их асинхронными. Это улучшает производительность и отзывчивость приложения.
- **Кеширование**: Кеширование используется для хранения часто используемых данных, что может значительно улучшить производительность приложения.
- **Соблюдение принципов SOLID**: Код разработан с учетом принципов SOLID, что обеспечивает более эффективную и поддерживаемую структуру.
- **Инъекция зависимостей**: Использование инъекции зависимостей помогает обеспечить гибкость, модульность и тестируемость кода. Это также способствует сокращению связности и повышению согласованности кода.
- **Документирование кода**: Использование комментариев и документирование кода является хорошей практикой, которая помогает другим разработчикам понимать ваш код и его назначение.
- **Соблюдение стандартов кодирования**: Код разработан в соответствии со стандартами кодирования, что улучшает его читаемость и поддерживаемость.

### Какие принципы SOLID тут использованы?

**SOLID** - это набор принципов проектирования объектно-ориентированных систем, которые обеспечивают хорошую поддерживаемость и читаемость кода. В приведенном коде можно увидеть следующие принципы:

- **Single Responsibility Principle (SRP)**, или Принцип единственной ответственности. Этот принцип можно увидеть в том, как разделены ответственности между контроллерами и службами. Контроллеры ответственны только за обработку запросов и отправку ответов, в то время как бизнес-логика реализована в службах.
- **Dependency Inversion Principle (DIP)**, или Принцип инверсии зависимостей. Этот принцип реализован через использование инъекции зависимостей. Вместо того чтобы контроллеры напрямую создавали экземпляры классов служб, они получают их через конструктор. Это увеличивает модульность и гибкость кода, так как зависимости могут быть легко заменены или изменены.

Остальные принципы SOLID (**Open-Closed Principle**, **Liskov Substitution Principle**, **Interface Segregation Principle**) могут быть применены при дальнейшем расширении и рефакторинге кода. Но это тестовое задание, по этому наврят ли 😜

### Какие принципы стандарты кодирования тут использованы?

В контексте программирования, стандарты кодирования - это набор правил и соглашений о том, как следует написать код. В этом коде соблюдаются стандарты кодирования Laravel и PSR-12:
- **Laravel Coding Standards**: Это соглашения, сформированные сообществом Laravel, которые касаются организации кода, именования классов, методов и переменных, и так далее.
- **PSR-12**: Это стандарт кодирования, определенный PHP-FIG, который устанавливает базовые правила о том, как следует форматировать PHP код. К нему относятся вещи, такие как расстановка пробелов, использование скобок, именование переменных и методов, и так далее.

Соблюдение этих стандартов помогает обеспечить консистентность и читаемость кода.

## Ссылки на докуметацию

### SOLID Principles

Принципы SOLID: вы можете прочитать больше о них на [Википедии](https://en.wikipedia.org/wiki/SOLID) или в [этом блоге на Medium](https://medium.com/backticks-tildes/the-s-o-l-i-d-principles-in-pictures-b34ce2f1e898).

### Laravel Coding Standards

Стандарты кодирования Laravel: Официальная документация Laravel не содержит конкретного раздела о стандартах кодирования. Однако, Laravel следует PSR-1, PSR-2 и PSR-4. Для дополнительных соглашений, таких как структура проекта или именование классов и методов, можно обратиться к [официальной документации Laravel](https://laravel.com/docs/8.x) или просто изучить [существующий код Laravel](https://github.com/laravel/laravel).

### PSR-12

Стандарт PSR-12: полное описание этого стандарта доступно на [официальном сайте PHP-FIG](https://www.php-fig.org/psr/psr-12/).

### PHP Standards Recommendations (PSR)

Все PSR (PHP Standards Recommendations): полный список всех рекомендаций PSR доступен на [официальном сайте PHP-FIG](https://www.php-fig.org/psr/). Каждая рекомендация имеет свою собственную страницу с подробным описанием и примерами кода.

### Прямые ссылки из приведенной документации
- https://en.wikipedia.org/wiki/SOLID
- https://medium.com/backticks-tildes/the-s-o-l-i-d-principles-in-pictures-b34ce2f1e898
- https://laravel.com/docs/10.x
- https://github.com/laravel/laravel
- https://www.php-fig.org/psr/psr-12/
- https://www.php-fig.org/psr/

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
