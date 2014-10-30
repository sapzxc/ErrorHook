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

Benefits
--------

1. Catching errors, notices, exceptions and send to notifier. Currently available Syslog and Mail notifiers.
2. Prevent sending duplicates (error/notices in cycles)
3. Mail notifier has good output with all detailed info need for debug.
4. Tested a lot time with big project and bit oututs. Fixed memory exceeded errors.
