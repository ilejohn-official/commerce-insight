# Consolidated Order Denormalisation System

## Table of contents

- [General Info](#general-info)
- [Requirements](#requirements)
- [Setup](#setup)
- [Usage](#usage)

## General Info

This project Provides insights into e-commerce data by Syncing and consolidating order data efficiently.

## Requirements

- [php ^8.3](https://www.php.net/ "PHP")

## Setup

- Clone the project and navigate to it's root path and install the required dependency packages using the below commands on the terminal/command line interface.

  ```bash
  git clone https://github.com/ilejohn-official/commerce-insight
  cd commerce-insight
  ```

  ```bash
  composer install
  ```

- Copy and paste the content of the .env.example file into a new file named .env in the same directory as the former and set it's  
  values based on your environment's configuration.

- Generate Application Key

  ```bash
  php artisan key:generate
  ```

- Run Migration

  ```bash
  php artisan migrate
  ```

- Run seeder. If you need to test up to a million orders and 500000 customers then you can use the seeder.

  ```bash
  php artisan db:seed
  ```

- Ensure the php redis extension is installed and that redis is running as this service uses Redis for queues.

  ```bash
  sudo apt-get install php-redis
  ```

- Enable LOCAL INFILE in MySQL Server

  ```bash
  SET GLOBAL local_infile = 1;
  ```

- Symlink storage directory to public

  ```bash
  php artisan storage:link
  ```

## Usage

- To run local server

  ```bash
  php artisan serve
  ```

- Run queue work or use supervisor if on a server

  ```bash
  php artisan queue:work
  ```

- Use csv files when importing
