# api-lumen

## Description

Outline all the project customization required to get this project working

### Fixes

The following fixes have been applied to get shit working

- Add `TELESCOPE_ENABLED=false` to `phpunit.xml` file to fix issues when running tests

    `phpunit.xml`
    ```
        <server name="TELESCOPE_ENABLED" value="false"/>
    ```

### License

Copyright &copy; 2019 Mike Erickson
Released under the MIT license

### Credits

api-laravel written by Mike Erickson
