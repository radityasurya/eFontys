// Default JavaScript Functions and Initiations
$(document).ready(function () {
  // Functions go here...
  $("a.close").click(function (event) {
    event.preventDefault();
    $(this).parent().fadeOut( "slow" )
  });

  setTimeout(function() {
      $("a.close").parent().fadeOut( "slow" )
  }, 5000);
}); // end document ready
