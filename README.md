# Simple PHP Backend-Only Restful API

## Background
We often edit our project files online by using HTTP Protocol such as using git editor, file manager at cpanel, or even vscode with FTP or SSH Connection. This project is one of them, just upload this project to your server and edit your project with freedom.

## Requirement
- Tested on PHP 8.1 or more

## Setup
Just clone with No dependencies, leave your composer require 😊
```sh
git clone https://github.com/starlight93/php-file-editor.git
```
Copy .env-example to .env and set as you want
```env
AUTHORIZATION_TOKEN=
GZIP_COMPRESSED_RESPONSE=true
WRITABLE_PATHS=
PREFIX_PATH=
```
Let's try with PHP simple http server using your CLI tool
```sh
php -S 127.0.0.1:8000
```

## Security
Such as common website contents, secure this deployed project files by using .env file, set your .htaccess file if you are using apache, and the the most important thing is setting the ownership or permission of the path to read-only or writable. The user who run this project will be the user who read and write the files. For example if you run `php -s ...` with current user, your user will be set as editor of the files, if you run `sudo php -s ...`, the root user will be set as editor of the files. Webservers such as `apache,nginx, or others` use their default own user when serving.

## Demo Restful API
[Demo Restful API](https://php-file-editor.vercel.app/)


## Demo Using Mini Vue App as API Consumer
[Demo Frontend](https://vue-file-editor.vercel.app/)

## Deploy Using Vercel
[Follow this guide](https://github.com/vercel-community/php)

## Features By Path Urls
- /getStructure : Get Dir and File Sturctures Recursively
- /getContent : Get File Content as Highlighted or Raw Text
- /setContent : Set new or existing writable file
- /getMeta : Get Meta Information of a path
- /delete : Delete existing writable file or empty dir

## As Library to Projects
- Copy `/Lib` Dir to your project, set the namespace of the classes to make sure the autoloader know these 2 files.


## 📝 License
Copyright © 2023 [starlight93](https://github.com/starlight93).
This project is [MIT](LICENSE) licensed.