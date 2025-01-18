# JuezLTI

This is a guide for JuezLTI developers


### Base tech stack:

---

- [PHP - 7.3.21](https://www.php.net/downloads.php)
- [Apache - 2.4.46](https://httpd.apache.org/download.cgi)
- [Node - 16.13.2](https://nodejs.org/en/blog/release/v16.13.2/)
- [Java - 8](https://www.oracle.com/es/java/technologies/javase/javase8-archive-downloads.html)
- [MySQL - 5.7](https://dev.mysql.com/downloads/mysql/5.7.html)
- [Spring-boot - 2.5.4](https://spring.io/)
- [MongoDB - 4.4.9](https://www.mongodb.com/try/download/community)


<br>

### Utilities and libraries:

---

- [Tsugi](https://www.tsugi.org/) - Customized
  - Forked from commit: ["Update the documentation to ean away from Issuer except for Canvas."](https://github.com/tsugiproject/tsugi/commit/cf347c5ea900512b39607c67c408e7079698a305) in cf347c
  - Customizations committed on top of the branch `codetest-customizations`

- [Docker](https://www.docker.com/)
  - The project is dockerized at [this repository](https://github.com/JuezLTI/deploy_docker)

- [Webpack Encore](https://symfony.com/doc/current/frontend.html)
  - Used to managed assets like images, css, js, among other utilities

- [Crowdin CLI](https://support.crowdin.com/cli-tool/)
  - Used to manage the translations

<br>

## Development environment

For development purposes the best approach is to setup all the necessary services at you local machine.

Services list:

- Tsugi
- Codetest
- Questions-storage
- MySQL
- MongoDB
- Webpack dev-server
- XML-validator
- Feedback-manager

This is the guide to follow to get a development environment configured:

> ENVIRONMENT SETUP

- Install Apache [ 2.4.46 ], PHP [ 7.3.21 ], Node [ 16.13.2 ] and Java [ 8 ]

- Configure a MySQL [ 5.7 ] and a MongoDB [ 4.4.9 ]

> TSUGI CONFIG

- Create a folder called "tsugi" inside your `www` folder

- Clone the branch `codetest-customizations` from the [Tsugi repo](https://github.com/JuezLTI/tsugi/tree/codetest-customizations) into that folder

- Create a file called `config.php` with the content of `config-dist.php` (from tsugi)

- Configure your MySQL credentials there

- Create a folder inside tsugi, for codetest: `www/tsugi/mod/codetest`

> CODETEST CONFIG

- Clone the [Codetest repo](https://github.com/JuezLTI/codetest/tree/master) inside that folder

- Create a file called `config.php` with the content of `config-dist.php` (from codetest)

- Configure your MySQL credentials there

- Inside the file `initTsugi.php` change the value of `Twig\Environment[debug]` to `true` so you can see the changes made to .twig files inmediatly (without caching)

- Install [Composer](https://getcomposer.org/)

- Then run this command inside the `codetest` folder

      composer update

- This will download the dependencies for PHP

- ⚠ PHP needs writing permissions to `www/tsugi/mod/codetest`

> FRONTEND SETUP

- Run the command:

      npm run dev

- This will run a webpack dev-server that will be watching for changes in the scss and js files and will put the results in the `public` folder

- Or run the command:

      npm run build

- This will build all the assets for production and will place them into the `public` folder

- ⚠ Depending if you are using the production build or the development build, maybe you will need to modify the file `www/tsugi/mod/codetest/views/dao/tool-footer.php.twig` to use one set of assets or another
s
- After that you will be able to access tsugi at `http://localhost/tsugi`

> MYSQL DB SETUP

- With tsugi running you need to upgrade the database (this will create all the tables needed for tsugi and codetest)

- Go to `http://localhost/tsugi/admin`

- Fill the password that is defined inside `www/tsugi/config.php >> $CFG->adminpw`

- Click 'Upgrade Database' and wait for the process to finish

- After that you will need to get the `questions-storage` up and running

> QUESTIONS-STORAGE SETUP

- Clone the [questions-storage repository](https://github.com/JuezLTI/questions-storage) 

- Configure your MongoDB credentials at `application.properties`

- Then run the following command:

      ./mvnw spring-boot:run

- At this point you should be able to access the questions-storage API at `http://localhost:8080/`

> CREATE USER

- After the spring server is online, you need to create an user, using the endpoint

      http://localhost:8080/api/auth/register

- To create an account you need to make a POST request with this data as json.<br> With the header "auth-secret" set to the value set inside the file `application.properties` at the property `auth.register.secret`

      {
          "username": "codetest",
          "password": "c0d3te5t",
          "email": "codetest@email.com"
      }

- Example of CURL command
  
      curl --location --request POST 'http://localhost:8080/api/auth/register' \
      --header 'auth-secret: shh' \
      --header 'Content-Type: application/json' \
      --data-raw '{
          "username": "codetest",
          "password": "c0d3te5t",
          "email": "codetest@email.com"
      }'

- ⚠ The user created in this step must be configured inside the codetest file `config.php`, inside the object `$CFG_CT->apiConfigs`

> SETUP XML-VALIDATOR

- Clone the branch `deployment` of [xml-validator repository](https://github.com/JuezLTI/xml-evaluator)

- For the next step you must set some environment variables, depending if you are using windows or linux this process will be different

- This is the list of environment variables you need to set:

  - FEEDBACK_MANAGER_URL
  - BASE_URL
  - PORT
  - EMAIL
  - PASSWORD

- Inside the folder `xml-evaluator/server` execute the command `npm run prod`

- After this you should be able to access the validator at the address `http://localhost:3000/`

> SETUP FEEDBACK-MANAGER

- Clone the branch `main` of [feedback-manager repository](https://github.com/JuezLTI/feedback-manager)

- For the next step you must set some environment variables, depending if you are using windows or linux this process will be different

- This is the list of environment variables you need to set:

  - PORT

- Inside de folder `feedback-manager/minimal feedback` execute the command `npm run prod`

- After this the feedback manager will be available at the address `http://localhost:3003/`

<br>

## Configure inside Moodle


After getting codetest up and running, you will need to configure the LTI tool inside Moodle

- Navigate to > Site administration > Plugins > Activity modules > External tool > [Manage Tools](http:///yourmoodle/mod/lti/toolconfigure.php)

- Click at `configure a tool manually`

- As Tool URL: `http://localhost/tsugi/mod/codetest/`
  
  > ⚠ Must have a trailing forward slash !!

- Select `LTI 1.0/1.1`

- Use `12345` as Consumer key

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
