# ReactPHP + Symfony example

**[`services.yml`](conf/services.yml)**

Base service definitions to enable Symfony to route requests, resolve controllers etc.

Most important lines:

```yaml
event_dispatcher:
  # ...
  calls:
    # ...
    - [ addListener, [ kernel.view, [ App\PromiseResponse, wrapPromise ] ] ]
```

`PromiseResponse` allows Symfony to return *something*. That something is a promise of the actual response.

**[`RunCommand.php`](src/App/Command/RunCommand.php)**

Starts HTTP server that converts ReactPHP requests to Symfony requests and then Symfony responses to ReactPHP responses.

**[`IndexController.php`](src/App/Controller/IndexController.php)**

An example controller. `indexAction` returns immediately response. `promiseAction` responds after X seconds waiting.

---

Run with:

```sh
$ ./app.php run
```