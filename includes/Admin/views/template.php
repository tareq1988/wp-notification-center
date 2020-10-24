<div tabindex="-1" class="wd-drawer wd-drawer-right" id="wd-drawer">
    <div class="wd-drawer-mask"></div>
    <div
        class="wd-drawer-content-wrapper"
        style="width: 350px; transform: translateX(100%);">
        <div class="wd-drawer-content">
            <div class="wd-drawer-wrapper-body">
                <div class="wd-drawer-header">
                    <div class="wd-drawer-title">
                        <?php _e( 'Notifications', 'notification-center' ); ?>
                        <a href="#" class=""><span class="dashicons dashicons-update"></span></a>
                    </div>
                    <div class="wd-drawer-read">
                        <a href="#" id="wd-notification-mark-all-read"><?php _e( 'Mark all as read', 'notification-center' ); ?></a>
                    </div>
                </div>
                <div class="wd-drawer-body">
                    <ul class="wd-notification-list" id="wd-notifications-list"></ul>
                </div>
            </div>
        </div>
    </div>
</div>
<script id="tmpl-wd-notifications" type="text/html">
    <# if (data.length) { #>
        <# _.each( data, function( item ) { #>
            <# var date = new Intl.DateTimeFormat('default', { hour12: true, month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric'}).format(new Date(item.date + 'Z')); #>
            <li class="wd-notification-item {{ item.read ? 'read' : 'unread' }}">
                <a href="{{ item.link }}" data-id="{{ item.id }}">
                    <div class="notif-icon">
                        <img src="{{ item.icon }}" alt="Icon">
                    </div>
                    <div class="notif-body">
                        <div class="notif-text">{{{ item.content }}}</div>
                        <div class="notif-date">{{ date }}</div>
                    </div>
                    <div class="notif-actions">
                        <span class="dashicons dashicons-marker"></span>
                    </div>
                </a>
            </li>
        <# } ) #>
    <# } else { #>
        <li>
            <?php _e( 'No notification found.', 'notification-center' ); ?>
        </li>
    <# } #>
</script>
