$(document).ready(function () {
   let menu = $('.main-buttons-inner-container'),
       group_id = $('.group_id_container').attr('data-id'),
       url = '/workgroups/group/' + group_id + '/schedule_payment/',
       schedule_wrapper = $('.crm-lead-schedule-payment-wrapper'),
       activeClass = schedule_wrapper.length > 0 ? 'main-buttons-item-active' : '';

   menu.find('[data-text="Основное"]').after('<div class="main-buttons-item ' + activeClass + '">' +
       '<a class="main-buttons-item-link" href="' + url + '">' +
       '<span class="main-buttons-item-icon"></span><span class="main-buttons-item-text">' +
       '<span class="main-buttons-item-edit-button"></span>' +
       '<span class="main-buttons-item-text-title">График платежей</span>' +
       '<span class="main-buttons-item-drag-button"></span>' +
       '<span class="main-buttons-item-text-marker"></span>' +
       '</span><span class="main-buttons-item-counter"></span>');
});