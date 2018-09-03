
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    //Gets the token's name as value.
    var token = $('#token').attr('name');
    var catId = $('#cat-id').val();

    //Runs the JQuery autocomplete.
    $('#filter-search').devbridgeAutocomplete({
      minChars: 2,
      lookup: function (query, done) {
	//Calls the ajax function of the component global controller.
	var urlQuery = {[token]:1, 'task':'ajax', 'format':'json', 'cat_id':catId, 'search':query};
	$.ajax({
	    type: 'GET', 
	    //url: '', 
	    dataType: 'json',
	    data: urlQuery,
	    //Get results as a json array.
	    success: function(results, textStatus, jqXHR) {
	      done(results.data);
	    },
	    error: function(jqXHR, textStatus, errorThrown) {
	      //Display the error.
	      alert(textStatus+': '+errorThrown);
	    }
	});
      },
      onSelect: function (suggestion) {
	//Submit form just after the user has selected a suggestion.
	snipf.submitForm();
      }
    });

  });


})(jQuery);
