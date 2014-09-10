ErrorHook
=========

Error Hook for PHP. Provide error handling throw syslog or emails.

Sample of use:
```php
// init errors hook
if(DEBUG)
{
  $errorNotifier = new \ErrorHook\SyslogNotifier(LOG_LOCAL0, 'ERRORS_LABEL');
}
else
{
  $errorNotifier = new \ErrorHook\MailNotifier("admin@site.com", "robot-noreply@site.com");
}

// init error catcher
new \ErrorHook\Catcher($errorNotifier);

```
