# Quiz-Back
This is the backend of the quiz application. It is a RESTful API that provides the following services:
- User management
- Quiz management
- Question management
- Answer management
- Result management
- Session management
- Authentication
- Authorization
- Registration
- Error handling 

## Setup Instructions
PHP 8.0.0 or higher, MariaDB 15.1 and Composer 2.2.6 are required to run this application.
MANDATORY: You need to run application  http://127.0.0.1:8000 Frontend is hardcoded on this address.
On 127.0.0.1:8000 You can find Request docs.

1. Clone the repository:
    ```bash
    git clone git@github.com:nodarchik/Quiz-Back.git
    ```

2. Navigate to the project directory:
    ```bash
    cd Quiz-Back
    ```

3. Install the dependencies:
    ```bash
    composer install
    ```

4. Copy the `.env.example` file to `.env`:
    ```bash
    cp .env.example .env
    ```

5. Generate an application key:
    ```bash
    php artisan key:generate
    ```
   
6. Import the database dump:
    ```bash
    mysql -u root -p laravel -h 127.0.0.1 -P 3306 < dump.sql
    ```

7. Run the database migrations:
    ```bash
    php artisan migrate
    ```

8. Start the Laravel development server:
    ```bash
    php artisan serve
    ```

Please note that you need to have Composer and PHP installed on your machine to run these commands. 
Also, make sure to set up your `.env` file with the correct database connection details before running the migrations.

