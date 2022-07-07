// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package     theme_university
 * @copyright   2022 Debonair Training Ltd, debonairtraining.com
 * @author      Debonair Dev Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//used this function to get the ID from the url
  function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)", "i"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
  }

  // save the ID gotten from the url and save it here
  var id = getParameterByName('id'); // 6

//passed the ID to a json function to get the information
  function get_details() {
    $.post("../theme/university/details.php", {id:id},
    function(data, status){
      //split the datas into array
      var res = data.split(",dikko,");

      document.getElementById("time_setting").innerHTML = res[0];
      document.getElementById("time_setting1").innerHTML = res[1];
      document.getElementById("time_setting2").innerHTML = res[2];
      document.getElementById("percentage").innerHTML = res[3];
      var id = res[3];
      //alert(id);
      // Create a progress element
            // var g = document.createElement("progress");
            //
            // // Set the value of progress element
            // g.setAttribute("value", id);
            //
            // // Set the maximum value of progress element
            // g.setAttribute("max", "100");
            //
            // // Get the value of progress element
            // document.getElementById("percentages").appendChild(g);
            //the progress bar
            var element = document.getElementById("percentages");
var width = 0;
var identity = setInterval(scene, 10);
function scene() {
  if (width >= id) {
    clearInterval(identity);
  } else {
    width++;
    element.style.width = width + '%';
  }
}
      });
  }
  get_details();
