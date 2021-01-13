# PECL Info

This project allows you to see the requirements of all PHP packages hosted in the [PHP PECL website](https://pecl.php.net), and it's available at [this URL](https://mlocati.github.io/pecl-info/).

## Collecting the PECL packages data

The data of the PECL packages are fetched via PHP.

In order to do that, you need to install [Composer](https://getcomposer.org) dependencies:

```sh
composer install
```

Then run the `update` command:

```sh
./bin/pecl-info update
```

## Building the web application

The web interface is created statically.

In order to do that, you need to install [NodeJS](https://nodejs.org) dependencies:

```sh
npm install
```

While developing, you can compile the application (with hot-reloading enabled):

```sh
npm run serve
```

To generate the production application:

```sh
npm run build
```

You can also lint the source files:

```sh
npm run lint
```

## Do you really want to say thank you?

You can offer me a [monthly coffee](https://github.com/sponsors/mlocati) or a [one-time coffee](https://paypal.me/mlocati) :wink:
