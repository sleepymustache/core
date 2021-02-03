# sleepyMUSTACHE

Detailed [Documentation] (http://www.sleepymustache.com/documentation/index.html) is available.

sleepyMUSTACHE is a PHP micro framework that has solutions for everyday PHP challenges. Most of the functionality is optional and tries to be as minimalist as possible.

### Core Functionality

These core modules are the shared bases for the Basic and Routed Frameworks.

* **[Debugging] (http://www.sleepymustache.com/documentation/class-Sleepy.Debug.html)** -
  Easily send debug information via the browser, email, or database.
* **[Hooks] (http://www.sleepymustache.com/documentation/class-Sleepy.Hook.html)** -
  Hooks allow you to run a function at certain spots in your code. We call these spots *hook points*. Multiple functions may be executed at any *hook point*.
* **[Templating] (http://www.sleepymustache.com/documentation/class-Sleepy.Template.html)** -
  Basic templating functionality lets you separate business logic from the
  view. It replaces placeholders like "{{ title }}" with data.
* **[Routing](http://www.sleepymustache.com/documentation/class-Sleepy.Router.html)** -
  A very basic routing class that allows you to build database driven applications.

### Setting Constants

* **ENV**
  What is the current environment. Values: DEV, STAGE, LIVE
* **URLBASE**
  The base URL to the sleepyMUSTACHE base directory
* **DIRBASE**
  The base directory to the sleepyMUSTACHE base directory
* **DBHOST**
  The mySQL host URL
* **DBUSER**
  the mySQL username
* **DBPASS**
  the mySQL password
* **DBNAME**
  the mySQL database name
* **EMAIL_FROM**
  The email address to use for the "from" field
* **EMAIL_TO**
  The email address to use for the "to" field
* **EMAIL_CC**
  The email address to use for the "cc" field
* **EMAIL_BCC**
  The email address to use for the "bcc" field
* **GA_ACCOUNT**
  The Google Analytics GA Account ID

### Hooks

The *Hooks* system is made up of *hook filters* and *hook actions*. *Hook actions* are points in the code where you can assign functions to run. For example, we can put a *hook action* after a record is saved to the database, then assign a function to the *hook action* that will send an email after the DB update. The *modules/enabled* directory provides a convenient location to put code that utilizes the hooks system. Code inside of the *modules/enabled* directory are automatically added to the program at runtime.

``` php
  namespace Module\Example;

  // Save to the database
  $db->save();

  // add a hook action
  \Sleepy\Hook::addAction('record_saved');

  // In the module file, add a function to the hook action
  function send_email() {
    // send an email saying a record was updated
  }

  \Sleepy\Hook::doAction(
    'record_saved',
    '\Module\Example\send_email'
  );
```

*Hook filters* are similar to *hook actions* but pass data as parameters to the functions that get assigned to the hook. After manipulating this data you must return the edited data back to the program.

``` php
  namespace Module\Example;

  // add a hook filter
  $content = \Sleepy\Hook::addFilter('update_content', $_POST['content']);

  // Add a function to the hook filter
  function clean_html ($html) {
    $c = htmlentities(trim($html), ENT_NOQUOTES, "UTF-8", false);
    return $c;
  }

  \Sleepy\Hook::applyFilter(
    'update_content',
    '\Module\Example\clean_html'
  );
```

### Templating

Templates reside inside the */app/templates/* folder and should end in a .tpl extension. The templating system works by using placeholders that later get replaced with text. The placeholders must have the following syntax:

``` php
  {{ placeholder }}
```

To use a template you instantiate the Template class passing in the template name. You then bind data to the placeholders and call the *Template::show()* method.

``` php
  require_once('include/sleepy.php');
  $page = new \Sleepy\Template('default');
  $page->bind('title', 'sleepyMUSTACHE');
  $page->bind('header', 'Hello world!');
  $page->show();
```

Here is the sample template file (templates/default.tpl):

``` html
  <html>
    <head>
      <title>{{ title }}</title>
    </head>
    <body>
      <h1>{{ header }}</h1>
      <p>This page has been viewed {{ hits }} times.</p>
    </body>
  </html>
```

We added a *{{ hits }}* placeholder in the template above. For this example, we want to replace the placeholder with the number of times this page was viewed. We can add that functionality using *Hooks*.

``` php
  // filename: /modules/hit-counter/hits.php
  namespace Module\Hits;

  /**
   * Adds the number of hits to the page.
   * @return string The total amount of hits
   */
  function get() {
    $hits = new FakeClass();
    return $hits->getTotal();
  }

  // Next we attach the function to the hook point
  \Sleepy\Hook::applyFilter(
    'render_placeholder_hits',
    '\Module\Hits\get'
  );
```

The first parameter of *\Sleepy\Hook::applyFilter()*, the *hook filter*, ends in 'hits' which correlates to the name of the placeholder. This *hook filter* is defined in *class.template.php*. The second parameter is the name of the function to run when we render the placeholder.

The templating engine allows you to iterate through multidimensional array data using #each placeholders.

``` php
  // Bind the data like this
  $page->bind('fruits', array(
    array(
      "name" => "apple",
      "color" => "red"
    ), array(
      "name" => "banana",
      "color" => "yellow"
    )
  ));

  // in the .tpl file
  {{ #each f in fruits }}
    <p>I like {{ f.color }}, because my {{ f.name }} is {{ f.color }}.</p>
  {{ /each }}
```

### Databases

The database connection settings are defined in the */app/settings.php* file.

To get a database instance, use:

``` php
  $db = \Module\DB\DB::getInstance();
```

The DB class is static and will automatically handle suppressing multiple database connections.

### Sending emails

The Mailer class simplifies sending emails by generating headers for you and using an easy to use object to clearly define your email. The Mailer can send emails using an HTML template or text.

``` php
  $m = new \Module\Mailer\Message();
  $m->addTo("test@test.com");
  $m->addFrom("from.me@test.com");
  $m->addSubject("This is a test, don't panic.");
  $m->fetchHTML("http://test.com/template.php");

  // OR

  $m->msgText("This is my message.")
  $m->send();
```

### Debugging

The *Debug* static class allows you to debug on-screen, via email, or by logging to a database.

``` php
  $db = \Module\DB\DB::getInstance();
  \Sleepy\Debug::out($db);
```

### Navigation

The navigation is generated from JSON. It renders the JSON into a unordered list with some classes added for the current active page.

``` php
  // Add a placeholder in your template
  {{ TopNav }}

  // Create a php file in */modules/enabled/*
  namespace Module\Example;

  require_once('include/class.navigation.php');

  // create a function to add to the *hook filter*
  function showNav() {
    // Page data is passed via JSON
    $topNavData = '{
      "pages": [
        {
          "title": "Nav 1",
          "link": "/nav1/"
        }, {
          "title": "Nav 2",
          "link": "/nav2/",
          "pages": [
            {
              "title": "Subnav 1",
              "link": "/downloads/fpo.pdf",
              "target": "_blank"
            }
          ]
        }
      ]
    }';

    $topNav = new \Module\Navigation\Builder($topNavData);
    $topNav->setCurrent($_SERVER['SCRIPT_NAME']);

    return $topNav->show();
  }

  \Sleepy\Hook::applyFilter(
    'render_placeholder_TopNav',
    '\Module\Example\showNav'
  );
```