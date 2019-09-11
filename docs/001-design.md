# Предисловие

1. [Описание подхода к системе событий на основе описания сигнатуры слушателя события](https://github.com/keepper/event-listener/blob/master/docs/001-introduction.md)
1. [Библиотека среализацией описанного подхода](https://github.com/keepper/event-listener/blob/master/docs/002-design.md)

# Проектирование

Для обеспечения легкого использования [event-listener](https://github.com/keepper/event-listener/) на базе IoC DI контейнера symfony, 
необходимо решить следующие задачи:

1. Регистрацию интерфейсов слушателей в ListenerManager
1. Регистрацию слушателей в ListenerManager
1. Ленивую инициализацию слушателей

## Регистрация интерфейсов слушателей

При разработке какого либо bundle, разработчик в конфигурации должен передать информацию о 
интерфейсах слушателей событий, которые генерируют сервисы в разрабатываемом bundle.

Список слушателей, по сути является массивом строк с названиями имен интерфейсов.

Как лучше реализовать конфигурирование?

Давайте посмотрим на это глазами разработчика.

Разработчик пишет сервис подтверждения email адреса, и он хочет предоставить стороннему коду возможность подписки на 
факт подтверждения адреса электронной почты путем обычного информирования без обратной связи.

```php
namespace Example\EmailConfirmationBundle\Services;

class EmailConfirmationService {

    // ..
    
    public function markEmailAddressAsConfirmed(string $email) {
        // ..
    }
    
    // ..
}
``` 

Во первых он должен описать интерфейс обработчика данного события.

```php
namespace Example\EmailConfirmationBundle\Events;

interface EmailConfirmedListenerInterface {
    
    public function onEmailConfirmed(string $email);
    
}
```

Во вторых в сервис ему нужно внедрить объект [NotificationDispatcher](https://github.com/keepper/event-listener/blob/master/src/Dispatcher/NotificationDispatcher.php)
для того, чтобы получить возможность сгенерировать событие.

```php
namespace Example\EmailConfirmationBundle\Services;

use Example\EmailConfirmationBundle\Events\EmailConfirmedListenerInterface;
use Keepper\EventListener\Dispatcher\NotificationDispatcher;

class EmailConfirmationService {

    /**
     * @var NotificationDispatcher
     */
    private $dispatcher;

    public function __construct(
        NotificationDispatcher $dispatcher
    ) {
        $this->dispatcher = $dispatcher;
    }

    // ..
    
    public function markEmailAddressAsConfirmed(string $email) {
        // ..
        
        // Генерируем событие
        $this->dispatcher->dispatch(EmailConfirmedListenerInterface::class, $email);
    }
    
    // ..
}
``` 

И не забыть сконфигурировать symfony сервис 

```yaml
services:

    # ..
    
    Example\EmailConfirmationBundle\Services\EmailConfirmationService:
      class: Example\EmailConfirmationBundle\Services\EmailConfirmationService
      arguments:
        - '@Keepper\EventListener\Dispatcher\NotificationDispatcher'
    
    # ..

```

Тоесть в любом сервисе который будет генерировать события, в конфигурации будет DI объекта диспатчера.

Как вариант реализации мы можем пометить такой сервис тегом, в параметре которого передать информацию об интерфейсах обработчиков событий.

```yaml
services:

    # ..
    
    Example\EmailConfirmationBundle\Services\EmailConfirmationService:
      class: Example\EmailConfirmationBundle\Services\EmailConfirmationService
      arguments:
        - '@Keepper\EventListener\Dispatcher\NotificationDispatcher'
      tags:
        -   name: listener.interface
            interface: 
              - 'Example\EmailConfirmationBundle\Events\EmailConfirmedListenerInterface' 
    
    # ..

```

За реализацию данного механизма отвечает [ListenerInterfaceRegistrationPass](../src/DependencyInjection/ListenerInterfaceRegistrationPass.php).

Дополнительно он позволяет указать через атрибут тега manager имя сервиса являющегося ListenerNamager для указанных событий. 
Это позволяет отказаться от глобального ListenerManager в тех случаях где это необходимо.

## Регистрация слушателей в ListenerManager

Тут все просто. Объявляем сервис который реализует какой либо интерфейс слушателя (или несколько) и помечаем его тегом слушателя

```yaml
services:

    # ..
    
    Example\LoggingSystem\EmailConfirmationListener:
      class: Example\LoggingSystem\EmailConfirmationListener
      tags:
        - name: event.listener

    # ..
    
```

## Ленивая инициализация слушателей

При обработке тега event.listener которым помечен какой либо сервис, мы должны зарегистрировать слушателя в
ListenerManager, путем вызова метода ListenerManager::addListener. 
Для этого нам придется передать в него экземпляр объекта сервиса, что означает его полную инициализацию.

Проблема в том, что это происходит на этапе инициализации контейнера, нам придется инициализировать, всех слушателей 
и все сервисы которые им требуются (поступают в конструктор или в инструкциях calls). В одном проекте, слушателей может 
быть множество, и это приведет к инициализации большого количества сервисов. Но, большинство из них не нужно для исполнения 
текущего запроса или команды.

Нам нужно, сделать подобную инициализацию ленивой, тоесть, инициализировать слушателя конкретного события только в момент когда это событие происходит.

Как этого достичь? 

## С помощью symfony lazy

С помощью ленивой [lazy](https://symfony.ru/doc/current/service_container/lazy_services.html) загрузки симфони.

Для этого все сервисы слушателейдолжны быть отмечены дополнительным свойством lazy. Мы можем это сделать автоматически через CompilerPass 
при обработке тега "event.listener".

За реализацию данного механизма отвечает [ListenerRegistrationPass](../src/DependencyInjection/ListenerRegistrationPass.php).

**Примечание**

Но! При добавлении зависимости от symfony/proxy-manager-bridge

```
composer require symfony/proxy-manager-bridge

Package operations: 5 installs, 0 updates, 0 removals
  - Installing ocramius/package-versions (1.4.0): Downloading (100%)         
  - Installing zendframework/zend-eventmanager (3.2.1): Downloading (100%)         
  - Installing zendframework/zend-code (3.3.2): Downloading (100%)         
  - Installing ocramius/proxy-manager (2.2.3): Downloading (100%)         
  - Installing symfony/proxy-manager-bridge (v4.3.4): Downloading (100%)  
```

Мы тянем по зависимости еще и **zendframework/zend-eventmanager** - хм, а зачем? Мы добавляем свою шину событий, и попутно тянем, стороннюю. 

 