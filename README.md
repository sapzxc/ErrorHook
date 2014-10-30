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

FAQ
---

1. How to add multiple addresses to mail notifier? 
 - Add mail forwarding in your email client for any other email addresses. 

2. How to configure syslog?
 - Add "local0.debug /var/log/errorhook.log" to your /etc/syslog.conf
 
3. How to configure mail sending? 
 - Setup postfix and follow instructions to setup relay_host. Most easier configuration is in this mail programm.

