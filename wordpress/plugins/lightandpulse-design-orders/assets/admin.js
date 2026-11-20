jQuery(function ($) {
  'use strict';
  // Pick proof/final files from the Media Library (or upload new ones).
  $('.lpdo-files-box').each(function () {
    var $box = $(this), $input = $box.find('input[type=hidden]'), $list = $box.find('.lpdo-file-list'), frame;
    function ids() { return $input.val() ? $input.val().split(',').filter(Boolean) : []; }
    $box.on('click', '.lpdo-add-files', function (e) {
      e.preventDefault();
      if (!frame) {
        frame = wp.media({ title: $box.find('h4').text(), multiple: true, button: { text: $(this).text() } });
        frame.on('select', function () {
          var current = ids();
          frame.state().get('selection').each(function (att) {
            var a = att.toJSON();
            if (current.indexOf(String(a.id)) > -1) { return; }
            current.push(String(a.id));
            var thumb = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.icon;
            $list.append($('<li>').attr('data-id', a.id).append($('<img>').attr('src', thumb).attr('alt', ''), $('<span>').text(a.filename), $('<button type="button" class="button-link lpdo-remove">×</button>')));
          });
          $input.val(current.join(','));
        });
      }
      frame.open();
    });
    $box.on('click', '.lpdo-remove', function () {
      var id = String($(this).closest('li').data('id'));
      $input.val(ids().filter(function (x) { return x !== id; }).join(','));
      $(this).closest('li').remove();
    });
  });
});
