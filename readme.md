# Notification Center

A notification center implementation for WordPress. Use this plugin to send user notification (visible only on the dashboard).

![Notification Center](http://tareq.in/ojSYXK+)

## Usage

From your plugin, you can send notifications to a user like this:

```php
wd_notify()->to( $receiver_id )
    ->body( '<strong>John Doe</strong> has commented on your post <strong>Hello World</strong>' )
    ->from_user( $sender_id )
    ->link( 'https://example.com/hello-world/' )
    ->send();
```

A plugin also can send a notification to a user:

```php
$plugin_basename = 'dokan-lite/dokan.php';

wd_notify()->to( $receiver_id )
    ->body( '<strong>Dokan Lite</strong> v2.4 is available, please upgrade.' )
    ->from_plugin( $plugin_basename )
    ->link( 'https://example.com/wp-admin/plugins.php' )
    ->send();
```

### Author

[Tareq Hasan](https://github.com/tareq1988)