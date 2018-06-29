//var hash = window.location.hash;
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    //var processAction = $('#process-action').val();
    var currentProcess = $('#current-process').val();
    $.fn.showCurrentProcessTab(currentProcess);
    $.fn.setProcessState(currentProcess);

    //Binds the deleting process link to a function.
    $('a[id^="btn-delete-"').click( function() { $.fn.warningMessage($(this)); });
  });


  $.fn.showCurrentProcessTab = function(currentProcess) {
    //By default in case no process exists.
    var hash = '#details';

    $('#myTabTabs li a').click(function(){
      window.location.hash = $(this).attr('href');
    });

    if(currentProcess) {
      //Shows the current process's tab.
      hash = '#process-'+currentProcess;
    }

    $('[href="'+hash+'"]').trigger('click');
  };


  $.fn.setProcessState = function(currentProcess) {
    $('[href="#process-'+currentProcess+'"]').css({'background-color': '#6cd26b', 'color': 'white'});
  };


  $.fn.warningMessage = function(obj) {

    if(confirm(Joomla.JText._('COM_SNIPF_WARNING_DELETE_PROCESS'))) {
      //Gets the process type and number from the link id.
      var regex = /-([a-z]+)-([0-9]+)$/;
      var matches = regex.exec(obj.attr('id'));
      var processType = matches[1];
      var processNb = matches[2];
      //Deletes the process.
      Joomla.submitbutton(processType+'.process.delete.'+processNb);
    }
  };
})(jQuery);

