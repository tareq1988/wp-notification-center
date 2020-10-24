;(function($) {

  var body = $('body'),
    bell = $('#wp-admin-bar-wd-notify-center'),
    drawer = $('#wd-drawer'),
    list = $('#wd-notifications-list'),
    hasNotification = false,
    lastFetched = null,
    hasBell = false;

  var Notification = {
    toggleDrawer: function() {
      if (drawer.hasClass('wd-drawer-open')) {
        Notification.closeDrawer();
      } else {
        Notification.openDrawer();

        if (hasBell) {
          Notification.dismissBell();
        }
      }
    },

    openDrawer: function() {
      drawer.addClass('wd-drawer-open');
      body.css('overflow', 'hidden');
      $(drawer).find('.wd-drawer-content-wrapper').css('transform', '');
    },

    closeDrawer: function() {
      drawer.removeClass('wd-drawer-open');
      body.css('overflow', '');

      $(drawer).find('.wd-drawer-content-wrapper').css('transform', 'translateX(100%)');
    },

    dismissBell: function() {
      Notification.post('dismiss', {})
        .done(function() {
          hasBell = true;
          Notification.removeBell();
        });
    },

    post: function(part, data) {
      part = part || '';

      return $.post({
        url: wdNotify.root + 'wd-notifications/v1/notifications/' + part,
        data: data,
        headers: { 'X-WP-Nonce': wdNotify.nonce }
      });
    },

    setLastFetched: function(response) {
      if (response.length) {
        lastFetched = response[0].timestamp;
      }
    },

    fetchAnimationStart: function() {
      drawer.find('.wd-drawer-title a').addClass('fetching');
    },

    fetchAnimationStop: function() {
      drawer.find('.wd-drawer-title a').removeClass('fetching');
    },

    fetch: function() {
      var param = wdNotify.root.includes('rest_route') ? '&' : '?';
      var query = ( lastFetched !== null ) ? param + 'since=' + lastFetched : '';

      Notification.fetchAnimationStart();

      $.get({
        url: wdNotify.root + 'wd-notifications/v1/notifications' + query,
        headers: { 'X-WP-Nonce': wdNotify.nonce }
      })
      .done(function(response, status, xhr) {
        Notification.fetchAnimationStop();
        Notification.setLastFetched(response);

        if (response.length) {
          if (hasNotification) {
            $(wp.template( 'wd-notifications' )(response)).hide().prependTo(list).fadeIn(1000);
          } else {
            list.append( wp.template( 'wd-notifications' )(response) );
          }

          var alert = xhr.getResponseHeader('x-wp-notification');

          if (alert === 'yes') {
            Notification.addBellIcon();
            hasBell = true;
          }
        }

        if (!hasNotification && response.length) {
          hasNotification = true;
        }
      });
    },

    refresh: function(e) {
      e.preventDefault();

      list.empty();

      lastFetched = null;
      hasNotification = false;

      Notification.fetch();
    },

    markRead: function(e) {
      e.preventDefault();

      var self = $(this),
        id = self.data('id');

      Notification.post(id, { status: 'read' })
        .done(function(response) {
          self.closest('.wd-notification-item').removeClass('unread').addClass('read');

          if (response.link && response.link !== '#' ) {
            window.location.href = response.link;
          }
        });
    },

    markAllRead: function(e) {
      e.preventDefault();

      Notification.post('read', {})
        .done(function(response) {
          list.find('li.unread').each(function(i, item) {
            $(item).removeClass('unread').addClass('read');
          });
        });
    },

    addBellIcon: function() {
      bell.find('.ab-icon').addClass('has-alert');
    },

    removeBell: function() {
      bell.find('.ab-icon').removeClass('has-alert');
    }
  };

  $(function() {

    bell.click(Notification.toggleDrawer);

    drawer.on('click', '.wd-drawer-mask', Notification.closeDrawer);
    drawer.on('click', '.wd-drawer-read a', Notification.markAllRead);
    drawer.on('click', '.wd-drawer-title a', Notification.refresh);

    list.on('click', '.wd-notification-item.unread a', Notification.markRead);

    // initial fetch after 200ms
    setTimeout(Notification.fetch, 500);

    // periodically fetch on a set duration
    setInterval(Notification.fetch, wdNotify.duration)
  });

})(jQuery);
