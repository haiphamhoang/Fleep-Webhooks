# Fleep PHP Webhook

Easy to use PHP library to post messages in Fleep using incoming webhook integrations.

# Usage
## Installation

Download Fleep.php and require/include it in your PHP file.

## Simple message

```php
include 'Fleep.php';

// Use the url you got earlier
$fleep = new Fleep('https://fleep.io/hook/Z95mXXXXXXXXXXyZZZ');

// Create a new message
$message = new FleepMessage($fleep);
$message->setMessage("Hello world!");

// Send it!
if ($message->send()) {
    echo "Hurray 😄";
} else {
    echo "Failed 😢";
}
```

## Attachments

Check out https://fleep.io/blog/integrations/webhooks/ for more details

```php
include 'Fleep.php';

// Use the url you got earlier
$fleep = new Fleep('https://fleep.io/hook/Z95mUpRYR6OKRFbNQ1y9aA');

// Create a new message
$message = new FleepMessage($fleep);
$message->setMessage("Hello world!");

$message->addAttachment('hello.jpg', file_get_contents('asset\1.jpg'), 'image/jpeg');


// Send it!
if ($message->send()) {
    echo "Hurray 😄";
} else {
    echo "Failed 😢";
}
```
### Add (multiple) attachments
```php
$message = new FleepMessage($fleep);
$message->addAttachment('hello1.jpg', file_get_contents('asset\1.jpg'), 'image/jpeg');
$message->addAttachment('hello2.txt', file_get_contents('data.txt'));
$message->send();
```

## Short syntax

All methods support a short syntax. E.g.:

```php
(new FleepMessage($fleep))
    ->addAttachment($filename1, $attachment1)
    ->addAttachment($filename2, $attachment2)
    ->send();
```

# Warning
Each message initiates a new HTTPS request, which takes some time. Don't send too much messages at once if you are not running your script in a background task.