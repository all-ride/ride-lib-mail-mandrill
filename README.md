# Ride: Mail Library (Mandrill)

Mandrill implementation for the mail library of the PHP Ride framework.

For more information, you can check the [Mandrill website](https://www.mandrill.com/).

## What's In This Library

### MandrillTransport

The _MandrillTransport_ class implements the _Transport_ interface.
It uses the Mandrill rest API to send mails.
You can set tags and the subaccount for all mails sent by the transport.

### MandrillMailMessage

The _MandrillMessage_ class extends the _MailMessage_ class.
You can set tags and the subaccount for a mail message individually.

## Code Sample

```php
<?php

use ride\library\log\Log;
use ride\library\mail\transport\MandrillTransport;

function createTransport($apiKey, Log $log) {
    $transport = new MandrillTransport($apiKey, $log);
    
    // a tag and subaccount to be set on all mails which don't set tags or a subaccount
    $transport->addTag('newsletter');
    $transport->setSubAccount('my-subaccount');
    
    return $transport;
}

function sendMail(MandrillTransport $transport) {
    // like any mail message
    $message = $transport->createMessage();
    $message->setSubject('My subject');
    $message->setRecipient('to@domain.com');
    $message->addCc('To 2 <to2@domain.com>');
    $message->addBcc(array('to3@domain.com', 'To 3 <to3@domain.com>'));
    $message->setIsHtmlMessage(true);
    $message->setMessage('<html><body><p>...</p></body></html>');
    
    // mandrill extension, override the transport tags and subaccount
    $message->addTag('registration');
    $message->setSubAccount('my-other-subaccount');
    
    // send it
    try {
        $transport->send($message);
    } catch (MailException $exception) {
        
    }
}
```

## Related Modules

- [ride/app-mail](https://github.com/all-ride/ride-app-mail)
- [ride/app-mail-mandrill](https://github.com/all-ride/ride-app-mail-mandrill)
- [ride/lib-log](https://github.com/all-ride/ride-lib-log)
- [ride/lib-mail](https://github.com/all-ride/ride-lib-mail-mandrill)

## Installation

You can use [Composer](http://getcomposer.org) to install this library.

```
composer require ride/lib-mail-mandrill
```
