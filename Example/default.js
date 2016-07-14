jQuery(document).ready(function ($) {

    jQuery('a.filter-country').on('click', function () {
        var code = jQuery(this).data('country');
        var isAll = (code == 'todos');

        jQuery('.homepage-content-wrapper').fadeTo(200, .4);
        //Delete cookie
        document.cookie = "Pais=; expires=Thu, 01 Jan 1970 00:00:00 UTC";
        //Insert cookie
        document.cookie = "Pais=" + code;

        var options = { url: "modules/mod_geoip/getuser.php" };

        jQuery.ajax(options)
              .done(function (data) {
                  document.getElementById("noticias").innerHTML = data;

                  SetCountryName(code);

                  jQuery('.homepage-content-wrapper').fadeTo(200, 1);
              })
              .fail(function (jqXHR, textStatus) {
                  console.log(jqXHR);
                  jQuery('.homepage-content-wrapper').fadeTo(200, 1);
              });

        jQuery(this).parents().removeClass("open");

        return false;
    });

    jQuery('a.filter-topic').on('click', function () {
        var el = jQuery(this);
        var code = el.data('topic');
        var isAll = (code == "tudo");
        jQuery('.homepage-content-wrapper').fadeTo(200, .4);

        //Delete cookie
        document.cookie = "tag=; expires=Thu, 01 Jan 1970 00:00:00 UTC";

        var options = { url: "modules/mod_geoip/getuser.php" };

        if (!isAll) {
            //Insert cookie
            document.cookie = "tag=" + code;
            options = { url: "modules/mod_geoip/getuser.php?tagid=" + code };
        }


        jQuery.ajax(options)
              .done(function (data) {
                  document.getElementById("noticias").innerHTML = data;

                  jQuery(".filter-topics li").removeClass("current active");
                  el.parent().addClass("current active");

                  jQuery('.homepage-content-wrapper').fadeTo(200, 1);
              })
              .fail(function (jqXHR, textStatus) {
                  console.log(jqXHR);
                  jQuery('.homepage-content-wrapper').fadeTo(200, 1);
              });

        return false;
    });

 
});
