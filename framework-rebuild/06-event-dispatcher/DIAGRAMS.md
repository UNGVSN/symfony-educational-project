# Event Dispatcher Visual Diagrams

Visual representations to help understand the Event Dispatcher component.

## Table of Contents

1. [Event Dispatcher Architecture](#event-dispatcher-architecture)
2. [Event Flow](#event-flow)
3. [HttpKernel Event Lifecycle](#httpkernel-event-lifecycle)
4. [Priority Execution](#priority-execution)
5. [Propagation Stopping](#propagation-stopping)
6. [Listener vs Subscriber](#listener-vs-subscriber)

## Event Dispatcher Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Event Dispatcher                          │
│                                                              │
│  ┌────────────────────────────────────────────────┐         │
│  │  Listener Storage                               │         │
│  │  ┌──────────────────────────────────────────┐  │         │
│  │  │ 'event.name' => [                        │  │         │
│  │  │   [callable1, priority: 100],            │  │         │
│  │  │   [callable2, priority: 50],             │  │         │
│  │  │   [callable3, priority: 0]               │  │         │
│  │  │ ]                                         │  │         │
│  │  └──────────────────────────────────────────┘  │         │
│  └────────────────────────────────────────────────┘         │
│                                                              │
│  ┌────────────────────────────────────────────────┐         │
│  │  Sorted Cache (for performance)                │         │
│  │  ┌──────────────────────────────────────────┐  │         │
│  │  │ 'event.name' => [                        │  │         │
│  │  │   callable1,  // Sorted by priority      │  │         │
│  │  │   callable2,                              │  │         │
│  │  │   callable3                               │  │         │
│  │  │ ]                                         │  │         │
│  │  └──────────────────────────────────────────┘  │         │
│  └────────────────────────────────────────────────┘         │
│                                                              │
│  Methods:                                                    │
│  • dispatch(event, eventName)                               │
│  • addListener(eventName, callable, priority)               │
│  • addSubscriber(subscriber)                                │
│  • removeListener(eventName, callable)                      │
│  • getListeners(eventName)                                  │
└─────────────────────────────────────────────────────────────┘
```

## Event Flow

### Basic Dispatch Flow

```
┌──────────────┐
│   Dispatch   │
│    Event     │
└──────┬───────┘
       │
       ▼
┌──────────────────────┐
│  Get Event Name      │
│  (class name if not  │
│   explicitly given)  │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│  Lookup Listeners    │
│  for Event Name      │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│  Sort by Priority    │
│  (if not cached)     │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────────────────────────┐
│  For Each Listener (high to low):        │
│  ┌────────────────────────────────────┐  │
│  │  1. Call listener(event)           │  │
│  │  2. Check isPropagationStopped()   │  │
│  │  3. If stopped, break loop         │  │
│  │  4. Otherwise, continue            │  │
│  └────────────────────────────────────┘  │
└──────────────────┬───────────────────────┘
                   │
                   ▼
            ┌──────────────┐
            │ Return Event │
            │  (modified)  │
            └──────────────┘
```

### Detailed Flow with Example

```
Application Code:
  $dispatcher->dispatch(new OrderPlacedEvent($order))
       │
       ▼
EventDispatcher::dispatch()
       │
       ├─→ eventName = 'OrderPlacedEvent'
       │
       ├─→ getListeners('OrderPlacedEvent')
       │   │
       │   ├─→ Check sorted cache
       │   │   Cache miss!
       │   │
       │   ├─→ Get raw listeners:
       │   │   [EmailListener, 100]
       │   │   [InventoryListener, 50]
       │   │   [AnalyticsListener, 0]
       │   │
       │   ├─→ Sort by priority:
       │   │   EmailListener (100)
       │   │   InventoryListener (50)
       │   │   AnalyticsListener (0)
       │   │
       │   └─→ Cache sorted list
       │
       ├─→ Call listeners in order:
       │   │
       │   ├─→ EmailListener($event)
       │   │   └─→ Sends confirmation email
       │   │   └─→ isPropagationStopped()? No
       │   │
       │   ├─→ InventoryListener($event)
       │   │   └─→ Updates stock
       │   │   └─→ isPropagationStopped()? No
       │   │
       │   └─→ AnalyticsListener($event)
       │       └─→ Tracks metrics
       │       └─→ isPropagationStopped()? No
       │
       └─→ Return $event
```

## HttpKernel Event Lifecycle

### Complete Request/Response Cycle

```
┌────────────────────────────────────────────────────────────┐
│                      HTTP REQUEST                          │
└──────────────────────┬─────────────────────────────────────┘
                       │
                       ▼
         ┌─────────────────────────────┐
         │   kernel.request EVENT      │
         │                             │
         │  Listeners (by priority):   │
         │  • RouterListener (32)      │◄── Match route
         │  • AuthListener (30)        │◄── Check auth
         │  • CacheListener (255)      │◄── Check cache
         │  • LocaleListener (16)      │◄── Set locale
         │                             │
         │  Can set Response & stop!   │
         └─────────────┬───────────────┘
                       │
                       │ No response set?
                       ▼
         ┌─────────────────────────────┐
         │  Resolve Controller         │
         │  (from request attributes)  │
         └─────────────┬───────────────┘
                       │
                       ▼
         ┌─────────────────────────────┐
         │  kernel.controller EVENT    │
         │                             │
         │  Can replace controller!    │
         │  • ParamConverterListener   │◄── Convert params
         │  • SensioFrameworkExtra     │◄── @Security, etc.
         └─────────────┬───────────────┘
                       │
                       ▼
         ┌─────────────────────────────┐
         │  Get Controller Arguments   │
         └─────────────┬───────────────┘
                       │
                       ▼
         ┌─────────────────────────────┐
         │   EXECUTE CONTROLLER        │◄── Your code runs here
         │   Returns Response          │
         └─────────────┬───────────────┘
                       │
                       ▼
         ┌─────────────────────────────┐
         │  kernel.response EVENT      │
         │                             │
         │  Listeners (by priority):   │
         │  • SessionListener          │◄── Save session
         │  • ResponseListener         │◄── Content-Type
         │  • ProfilerListener (-100)  │◄── Add profiler
         │  • WebDebugToolbar (-128)   │◄── Add toolbar
         │                             │
         │  Modify Response            │
         └─────────────┬───────────────┘
                       │
                       ▼
┌────────────────────────────────────────────────────────────┐
│                     HTTP RESPONSE                          │
└────────────────────────────────────────────────────────────┘

         Exception Path:
         Any Exception
                │
                ▼
         ┌─────────────────────────────┐
         │  kernel.exception EVENT     │
         │                             │
         │  • ExceptionListener        │◄── Create error response
         │  • LoggerListener           │◄── Log error
         │  • SentryListener           │◄── Report to Sentry
         │                             │
         │  Can set Response           │
         └─────────────┬───────────────┘
                       │
                       │ Response set?
                       ▼
         ┌─────────────────────────────┐
         │  kernel.response EVENT      │
         │  (same as above)            │
         └─────────────────────────────┘
```

### Timeline View

```
Time ─────────────────────────────────────────────────────►

T0: Request arrives
│
├─ T1: kernel.request dispatched
│  ├─ CacheListener (priority: 255) ────────┐ Cache miss
│  ├─ RouterListener (priority: 32) ────────┤ Route matched
│  ├─ AuthListener (priority: 30) ──────────┤ User authenticated
│  └─ LocaleListener (priority: 16) ────────┘ Locale set
│
├─ T2: Controller resolved
│
├─ T3: kernel.controller dispatched
│  └─ ParamConverterListener ───────────────┐ Params converted
│
├─ T4: Controller executed ═══════════════  Controller runs
│
├─ T5: kernel.response dispatched
│  ├─ SessionListener (priority: 0) ────────┤ Session saved
│  ├─ ProfilerListener (priority: -100) ────┤ Data collected
│  └─ WebDebugToolbar (priority: -128) ─────┘ Toolbar added
│
└─ T6: Response sent
```

## Priority Execution

### How Priority Works

```
Priority:    255         100         50          0          -100        -255
             │           │           │           │           │           │
             ▼           ▼           ▼           ▼           ▼           ▼
          ┌──────┐   ┌──────┐   ┌──────┐   ┌──────┐   ┌──────┐   ┌──────┐
          │  L1  │   │  L2  │   │  L3  │   │  L4  │   │  L5  │   │  L6  │
          └──────┘   └──────┘   └──────┘   └──────┘   └──────┘   └──────┘
          Earliest                                                    Latest
          Cache    Security   Routing    Business   Logging      Debug
```

### Priority Zones

```
┌─────────────────────────────────────────────────────────────┐
│  PRIORITY ZONE                TYPICAL USE                   │
├─────────────────────────────────────────────────────────────┤
│  255 to 200                   HTTP Cache                    │
│  • Very early execution       • Early return with cache     │
│  • Can stop propagation                                     │
├─────────────────────────────────────────────────────────────┤
│  200 to 100                   Security & Auth               │
│  • Authentication checks      • Firewall                    │
│  • Authorization              • CSRF protection             │
│  • Can stop propagation                                     │
├─────────────────────────────────────────────────────────────┤
│  100 to 50                    Core Processing               │
│  • Routing                    • Route matching              │
│  • Request preprocessing      • Locale detection            │
│  • Usually don't stop                                       │
├─────────────────────────────────────────────────────────────┤
│  50 to 0                      Normal Business Logic         │
│  • Application listeners      • Event handlers              │
│  • Business rules             • Domain logic                │
│  • Default priority (0)                                     │
├─────────────────────────────────────────────────────────────┤
│  0 to -50                     Post-Processing               │
│  • Response modification      • Header manipulation         │
│  • Content transformation     • Compression                 │
├─────────────────────────────────────────────────────────────┤
│  -50 to -100                  Finalization                  │
│  • Session handling           • Cookie setting              │
│  • Cache storage              • Metrics collection          │
├─────────────────────────────────────────────────────────────┤
│  -100 to -255                 Debug & Logging               │
│  • Profiler                   • Debug toolbar               │
│  • Logging                    • Performance tracking        │
│  • Very late execution        • Should never stop           │
└─────────────────────────────────────────────────────────────┘
```

## Propagation Stopping

### Without Stopping

```
Event Dispatched
    │
    ├─→ Listener 1 (priority: 100) ──→ Executes
    │
    ├─→ Listener 2 (priority: 50)  ──→ Executes
    │
    ├─→ Listener 3 (priority: 0)   ──→ Executes
    │
    └─→ Listener 4 (priority: -50) ──→ Executes

All listeners execute
```

### With Stopping

```
Event Dispatched
    │
    ├─→ Listener 1 (priority: 100) ──→ Executes
    │                                   └─→ event.stopPropagation()
    │
    ├─→ Listener 2 (priority: 50)  ──→ SKIPPED ✗
    │
    ├─→ Listener 3 (priority: 0)   ──→ SKIPPED ✗
    │
    └─→ Listener 4 (priority: -50) ──→ SKIPPED ✗

Propagation stopped, remaining listeners don't run
```

### Real Example: Cache Hit

```
kernel.request Event

┌─────────────────────────────────────────────┐
│ CacheListener (priority: 255)               │
│                                             │
│ if ($cached = $cache->get($request)) {      │
│     $event->setResponse($cached);           │
│     $event->stopPropagation(); ◄────────────┼── STOPS HERE
│ }                                           │
└─────────────────────────────────────────────┘
                │
                ▼ Propagation stopped!
┌─────────────────────────────────────────────┐
│ RouterListener (priority: 32)               │
│ SKIPPED - Won't execute ✗                   │
└─────────────────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────────┐
│ AuthListener (priority: 30)                 │
│ SKIPPED - Won't execute ✗                   │
└─────────────────────────────────────────────┘
                │
                ▼
        Controller won't run!
                │
                ▼
        Jump to kernel.response
```

## Listener vs Subscriber

### Listener Registration

```
┌─────────────────────────────────────┐
│        Listener (External)          │
├─────────────────────────────────────┤
│                                     │
│  // The listener class              │
│  class MyListener {                 │
│      public function onEvent($e) {  │
│          // Handle                  │
│      }                              │
│  }                                  │
│                                     │
│  // External registration           │
│  $dispatcher->addListener(          │
│      'event.name',                  │
│      [$listener, 'onEvent'],        │
│      10  // priority                │
│  );                                 │
│                                     │
└─────────────────────────────────────┘
```

### Subscriber Registration

```
┌──────────────────────────────────────────────────┐
│          Subscriber (Self-Configuring)           │
├──────────────────────────────────────────────────┤
│                                                  │
│  class MySubscriber implements                  │
│      EventSubscriberInterface {                 │
│                                                  │
│      // Configuration in the class itself       │
│      public static function                     │
│          getSubscribedEvents(): array {         │
│          return [                               │
│              'event.one' => 'onEventOne',       │
│              'event.two' => ['onEventTwo', 10], │
│              'event.three' => [                 │
│                  ['handleFirst', 100],          │
│                  ['handleSecond', -100]         │
│              ]                                  │
│          ];                                     │
│      }                                          │
│                                                  │
│      public function onEventOne($e) { }         │
│      public function onEventTwo($e) { }         │
│      public function handleFirst($e) { }        │
│      public function handleSecond($e) { }       │
│  }                                              │
│                                                  │
│  // Simple registration                         │
│  $dispatcher->addSubscriber(                    │
│      new MySubscriber()                         │
│  );                                             │
│                                                  │
└──────────────────────────────────────────────────┘
```

### Comparison

```
┌───────────────────┬─────────────────┬──────────────────┐
│     Feature       │    Listener     │    Subscriber    │
├───────────────────┼─────────────────┼──────────────────┤
│ Configuration     │ External        │ Self-contained   │
│ Complexity        │ Simple          │ Can be complex   │
│ Multiple events   │ One at a time   │ Many at once     │
│ Priorities        │ External        │ In class         │
│ Use case          │ Simple cases    │ Production code  │
│ Maintainability   │ Lower           │ Higher           │
│ Flexibility       │ Higher          │ Lower            │
└───────────────────┴─────────────────┴──────────────────┘
```

## Event Modification Pattern

```
┌──────────────────────────────────────────────────────────┐
│              Event with Modifiable Data                  │
└──────────────────────────────────────────────────────────┘

Initial State:
┌─────────────────────┐
│  PriceEvent         │
│  base: $100         │
│  discounts: []      │
└─────────────────────┘
        │
        ▼
┌─────────────────────────────────────────────────────────┐
│  Listener 1: LoyaltyDiscountListener (priority: 100)   │
│  discounts[] = 'loyalty: -$10'                          │
└─────────────────────────────────────────────────────────┘
        │
        ▼
┌─────────────────────┐
│  PriceEvent         │
│  base: $100         │
│  discounts: [-$10]  │
└─────────────────────┘
        │
        ▼
┌─────────────────────────────────────────────────────────┐
│  Listener 2: BulkDiscountListener (priority: 50)       │
│  discounts[] = 'bulk: -$15'                             │
└─────────────────────────────────────────────────────────┘
        │
        ▼
┌─────────────────────┐
│  PriceEvent         │
│  base: $100         │
│  discounts:         │
│    - loyalty: -$10  │
│    - bulk: -$15     │
└─────────────────────┘
        │
        ▼
Final: getTotal() = $100 - $10 - $15 = $75
```

## Summary

These diagrams illustrate:
- How the EventDispatcher stores and retrieves listeners
- The flow of event dispatching
- The complete HttpKernel event lifecycle
- How priority affects execution order
- How propagation stopping works
- The difference between listeners and subscribers
- How events can be modified by multiple listeners

Use these as reference when designing your event-driven architecture!
