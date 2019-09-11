# Event Listener Bundle

Symfony bundle добавляющий  поддержку [event-listener](https://github.com/keepper/event-listener/) описания событий

Смотри документ [проектирование](docs/001-design.md) для понимания, что? зачем? почему? 

## Использование

### Генерация событий

1. Описать интерфейсы слушателей событий

```php
namespace MyProject\MyPackage\Events;

interface MyEventListener {
    public function onMyEventFired(int $someParamOne, string SomeParamTwo = null);
}
```

2. В класс, который будет генерировать какое либо событие добавить DI Dispatcher и код генерации события.

```php
namespace MyProject\MyPackage\Services;

use MyProject\MyPackage\Events\MyEventListener;

class MyCoolService {
    
    /**
     * @var NotificationDispatcher
     */
    private $dispatcher;
    
    public function __construct(
        NotificationDispatcher $dispatcher
    ) {
        $this->dispatcher = $dispatcher;
    }
    
    public function someBussinesLogic() {
    
        // ...
        
        // Генерируем событие
        $this->dispatcher->dispatch(MyEventListener::class, 1, 'two');
        
        // ...
    }
}
```

3. При конфигурации класс, который генерирует событие, добавить информацию о интерфейсах слушателя

```yaml
    MyProject\MyPackage\Services\MyCoolService:
    tags:
      - name: listener.interface
        interface: MyProject\MyPackage\Events\MyEventListener
```

Или, если их несколько:

```yaml
    MyProject\MyPackage\Services\MyCoolService:
    tags:
      - name: listener.interface
        interface: 
          - MyProject\MyPackage\Events\MyEventListenerOne
          - MyProject\MyPackage\Events\MyEventListenerTwo
```

### Подписка слушателя событий

1. Написать класс реализующий какой либо из интерфейсов обработчиков событий

```php
namespace MyProject\AnotherPackage\Listeners;

use MyProject\MyPackage\Events\MyEventListener;

class SomeMyEventListener implements MyEventListener {

    public function onMyEventFired(
        int $someParamOne, 
        string SomeParamTwo = null
    ) {
        // Do something
    }    
}
``` 

2. При конфигурации отметить тегом слушателя событий

```yaml
    MyProject\AnotherPackage\Listeners\SomeMyEventListener:
    tags:
      - name: event.listener.interface
```
