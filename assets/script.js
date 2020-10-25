;(function($) {

  var body = $('body'),
    bell = $('#wp-admin-bar-wd-notify-center'),
    drawer = $('#wd-drawer'),
    list = $('#wd-notifications-list'),
    hasNotification = false,
    lastFetched = null,
    hasBell = false;

  var Notify = {
    toggleDrawer: function() {
      if (drawer.hasClass('wd-drawer-open')) {
        Notify.closeDrawer();
      } else {
        Notify.openDrawer();

        if (hasBell) {
          Notify.dismissBell();
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
      Notify.post('dismiss', {})
        .done(function() {
          hasBell = true;
          Notify.removeBell();
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

      Notify.fetchAnimationStart();

      $.get({
        url: wdNotify.root + 'wd-notifications/v1/notifications' + query,
        headers: { 'X-WP-Nonce': wdNotify.nonce }
      })
      .done(function(response, status, xhr) {
        Notify.fetchAnimationStop();

        if (response.length) {
          if (hasNotification) {
            $(wp.template( 'wd-notifications' )(response)).hide().prependTo(list).fadeIn(1000);
          } else {
            list.append( wp.template( 'wd-notifications' )(response) );
          }

          var alert = xhr.getResponseHeader('x-wp-notification');

          if (alert === 'yes') {
            Notify.addBellIcon();
            hasBell = true;

            // only show notifications if it came in real-time
            // Skip the first fetch
            if (lastFetched !== null && Notify.canShowBrowserNotification()) {
              response.forEach(function(item) {
                Notify.showBrowserNotification(item);
              });
            }
          }
        }

        if (!hasNotification && response.length) {
          hasNotification = true;
        }

        Notify.setLastFetched(response);
      });
    },

    refresh: function(e) {
      e.preventDefault();

      list.empty();

      lastFetched = null;
      hasNotification = false;

      Notify.fetch();
    },

    markRead: function(e) {
      e.preventDefault();

      var self = $(this),
        id = self.data('id');

      Notify.post(id, { status: 'read' })
        .done(function(response) {
          self.closest('.wd-notification-item').removeClass('unread').addClass('read');

          if (response.link && response.link !== '#' ) {
            window.location.href = response.link;
          }
        });
    },

    markAllRead: function(e) {
      e.preventDefault();

      Notify.post('read', {})
        .done(function(response) {
          list.find('li.unread').each(function(i, item) {
            $(item).removeClass('unread').addClass('read');
          });
        });
    },

    canShowBrowserNotification: function() {
      if (!('Notification' in window)) {
        console.log('This browser does not support desktop notification');
        return false;
      }

      if (Notification.permission !== 'granted') {
        console.log("We don't have notification permission");
        return false;
      }

      return true;
    },

    showBrowserNotification: function(item) {
      const notification = new Notification(item.origin, {
        body: item.content,
        icon: item.icon,
        timestamp: item.timestamp,
      })

      if (item.link) {
        notification.onclick = function(e) {
          window.location.href = item.link;
        }
      }
    },

    addBellIcon: function() {
      bell.find('.ab-icon').addClass('has-alert');
    },

    removeBell: function() {
      bell.find('.ab-icon').removeClass('has-alert');
    }
  };

  $(function() {

    bell.click(Notify.toggleDrawer);

    drawer.on('click', '.wd-drawer-mask', Notify.closeDrawer);
    drawer.on('click', '.wd-drawer-read a', Notify.markAllRead);
    drawer.on('click', '.wd-drawer-title a', Notify.refresh);

    list.on('click', '.wd-notification-item.unread a', Notify.markRead);

    // initial fetch after 500ms
    setTimeout(Notify.fetch, 500);

    // periodically fetch on a set duration
    setInterval(Notify.fetch, wdNotify.duration)
  });

})(jQuery);
