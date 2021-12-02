# JuezLTI

This is a guide for JuezLTI developers


### Base tech stack:

---

- [PHP - 7.3.21](https://www.php.net/downloads.php)
- [Apache - 2.4.46](https://httpd.apache.org/download.cgi)
- [Node - 14.15.1](https://nodejs.org/en/blog/release/v14.15.1/)
- [Java - 8](https://www.oracle.com/es/java/technologies/javase/javase8-archive-downloads.html)
- [MySQL - 5.7](https://dev.mysql.com/downloads/mysql/5.7.html)
- [Spring-boot - 2.5.4](https://spring.io/)
- [MongoDB - 4.4.9](https://www.mongodb.com/try/download/community)


<br>

### Utilities and libraries:

---

- [Tsugi](https://www.tsugi.org/) - Customized
  - Forked from commit: ["Revert JSON changes introduced in 6cd66ce27"](https://github.com/tsugiproject/tsugi/commit/bf25e87870e2673a0efbb0e995b905bd0188a233)
  - Customizations committed on top of master

- [Docker](https://www.docker.com/)
  - Used to have an easy to setup environment

- [Webpack Encore](https://symfony.com/doc/current/frontend.html)
  - Used to managed assets like images, css, js, among other utilities

- [Crowdin CLI](https://support.crowdin.com/cli-tool/)
  - Used to manage the translations

<br>

## Development environment

---

This is the guide to follow to get a development environment configured:

- Install Apache [ 2.4.46 ], PHP [ 7.3.21 ], Node [ 14.15.1 ] and Java [ 8 ]

- Configure a MySQL [ 5.7 ] and a MongoDB [ 4.4.9 ]

- Create a folder called "tsugi" inside your `www` folder

- Clone the branch `codetest-customizations` from the [Tsugi repo](https://github.com/KA226-COVID/tsugi/tree/codetest-customizations) into that folder

- Create a file called `config.php` with the content of `config-dist.php` (from tsugi)

- Configure your MySQL credentials there

- Create a folder inside tsugi, for codetest: `www/tsugi/mod/codetest`

- Clone the [Codetest repo](https://github.com/KA226-COVID/codetest/tree/master) inside that folder

- Create a file called `config.php` with the content of `config-dist.php` (from codetest)

- Configure your MySQL credentials there

- Inside the file `initTsugi.php` change the value of `Twig\Environment[debug]` to `true` so you can see the changes made to .twig files inmediatly (without caching)

> ⚠ PHP needs writing permissions to `www/tsugi/mod/codetest`

- Run the command:

      npm run dev

- This will run a webpack dev-server that will be watching for changes in the scss and js files and will put the results in the `public` folder

- Or run the command:

      npm run build

- This will build all the assets for production and will place them into the `public` folder

<br>

> ⚠ Depending if you are using the production build or the development build, maybe you will need to modify the file `www/tsugi/mod/codetest/views/dao/tool-footer.php.twig` to use one set of assets or another

<br>

> After that you will be able to access tsugi at `http://localhost/tsugi`

<br>

- With tsugi running you need to upgrade the database (this will create all the tables needed for tsugi and codetest)

- Go to `http://localhost/tsugi/admin`

- Fill the password that is defined inside `www/tsugi/config.php >> $CFG->adminpw`

- Click 'Upgrade Database' and wait for the process to finish

<br>

- After that you will need to get the `exercises-storage` up and running

- Clone the [exercises-storage](https://github.com/KA226-COVID/exercises-storage) repository

- Configure your MongoDB credentials at `application.properties`

- Then run the following command:

      ./mvnw spring-boot:run

> After that you will be able to access tsugi at `http://localhost:8080/`

<br>

## Docker

---

The project has a docker-compose with everything needed to run a full instance of JuezLTI

It's composed by:

- A node with Apache [ 2.4.46 ] and PHP [ 7.3.21 ]
  - Inside has tsugi and codetest installed

- A node with Java [ 8 ]
  - Inside has the code for the exercises-storage

- A node with MongoDB [ 4.4.9 ]
  - To be used by SpringBoot

- A node with MySQL [ 5.7 ]
  - To be used by Tsugi and Codetest

<br>

If you have Docker and docker-compose installed

To get the docker environment running just run the command:

    docker-compose up

<br>

> After the docker-initialization is done you will be able to access tsugi at `http://localhost/tsugi`

<br>

## Configure inside Moodle

After getting codetest up and running, you will need to configure the LTI tool inside Moodle

- Navigate to > Site administration > Plugins > Activity modules > External tool > [Manage Tools](http:///yourmoodle/mod/lti/toolconfigure.php)

- Click at `configure a tool manually`

- As Tool URL: `http://localhost/tsugi/mod/codetest/`

- Select `LTI 1.0/1.1`

- Use `123456` as Consumer key

- Use `secret` as Consumer secret

> ⚠ Those are initial values from tsugi, can be created/updated at tsugi licenses management

- Fill `Custom parameters` with ` timezone=$Person.address.timezone`

- Save the changes

<br>

- Now navigate to a course where your user is able to edit

- Turn editing on

- Add an activity or resource

- Select "External tool"

- Put a name

- Select the previously configured external tool

- Save and display

- You should see the splash screen of codetest


## Translations

---

> ⚠ To follow this steps you must have installed the [Crowdin CLI](https://support.crowdin.com/cli-tool/), there's multiple ways to install it.

If you need more information about how to install it, follow [this link](https://support.crowdin.com/cli-tool/)

To check if this is installed, open a terminal inside the project folder and try the following command:

    crowdin

The output should be similar to this:

![Crowdin CLI Working](https://support.crowdin.com/assets/docs/cli-v3@1x.png)

<br>

Besides having the CLI tool installed you need to get an access token to perform actions:

<br>

### Get access token

---

Navigate to > Crowdin > Settings > API > [Personal Access Tokens](https://crowdin.com/settings#api-key)

Then generate a new token:

- Choose a name

- Copy the token value


Create a new file at the root folder of the project, called ".env", based on the file ".env.example"

Then place the value copied from the previous step after the text `CROWDIN_PERSONAL_TOKEN=`


After the CLI tool is installed you will be able to perform the [following actions](https://support.crowdin.com/cli-tool/#usage):

<br>

### Crowdin CLI actions

---

    crowdin upload sources

- This will upload all the base translations to Crowdin

---

    crowdin upload translations

- This will upload all the translations to Crowdin

---

    crowdin download sources

- This will download all the base translations from Crowdin

---

    crowdin download

- This will download all the translations from Crowdin

---