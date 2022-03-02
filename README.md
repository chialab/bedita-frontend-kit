# Chialab/FrontendKit

The **Frontend Kit** is a [BEdita 4](https://www.bedita.com/) plugin designed to help in developing frontends.  
It covers objects routing and loading, views composition and auth-based staging sites.

## Usage

You can install the plugin using [Composer](https://getcomposer.org).

The recommended way to install Composer packages is:

```sh
$ composer require chialab/frontend-kit
```

Then, you have to load it as plugin in your Cake application:

**src/Application.php**
```php
$this->addPlugin('Chialab/FrontendKit');
```

Please read the [Wiki](https://github.com/chialab/bedita-frontend-kit/wiki) to correctly setup the frontend.


## Testing

Since some FrontendKit queries uses specific MySQL syntax, you must provide a DSN url for a test database before running tests:

```sh
$ export db_dsn='mysql://root:****@localhost/bedita4_frontendkit'
```

Then, you can launch tests using the `test` composer command:

```sh
$ composer run test
```

---

## License

**Chialab/FrontendKit** is released under the [MIT](https://gitlab.com/chialab/bedita-frontend-kit/-/blob/main/LICENSE) license.

