'use strict';

document.addEventListener('DOMContentLoaded', function () {

  // form validation

  var $target;
  var validname = true;
  var validinst = true;
  var validemail = true;
  var validlic = true;
  var x = "";

  $target = document.getElementById("name");
  if ($target) {
    x = $target.value;
    if( x.length < 2 ) {
      $target.classList.remove('is-success');
      $target.classList.add('is-danger');
      document.getElementById("namehelp").display = '';
      document.getElementById("namehelp").value = "Please provide a valid name.";
      document.getElementById("namehelp").classList.remove('is-success');
      document.getElementById("namehelp").classList.add('is-danger');
      validname = false;
    }
    else {
      $target.classList.remove('is-danger');
      $target.classList.add('is-success');
      document.getElementById("namehelp").display = 'none';
      validname = true;
    }
  }

  $target = document.getElementById("institution");
  if ($target) {
    x = $target.value;
    if( x.length < 2 ) {
      $target.classList.remove('is-success');
      $target.classList.add('is-danger');
      document.getElementById("insthelp").display = '';
      document.getElementById("insthelp").value = "Please provide a valid institution/university/company name.";
      document.getElementById("insthelp").classList.remove('is-success');
      document.getElementById("insthelp").classList.add('is-danger');
      validinst = false;
    }
    else {
      $target.classList.remove('is-danger');
      $target.classList.add('is-success');
      document.getElementById("insthelp").display = 'none';
      validinst = true;
    }
  }

  $target = document.getElementById("email");
  if ($target) {
    x = $target.value;
    var atpos = x.indexOf("@");
    var dotpos = x.lastIndexOf(".");
    if (atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= x.length) {
      $target.classList.remove('is-success');
      $target.classList.add('is-danger');
      document.getElementById("emailhelp").display = '';
      document.getElementById("emailhelp").value = "Please provide a valid e-mail address.";
      document.getElementById("emailhelp").classList.remove('is-success');
      document.getElementById("emailhelp").classList.add('is-danger');
      validemail = false;
    }
    else {
      $target.classList.remove('is-danger');
      $target.classList.add('is-success');
      document.getElementById("emailhelp").display = 'none';
      validemail = true;
    }
  }

  $target = document.getElementById("license");
  if ($target) {
    if (!$target.checked) {	
      document.getElementById("lichelp").display = '';
      document.getElementById("lichelp").value = "You must comply to the license!";
      document.getElementById("lichelp").classList.remove('is-success');
      document.getElementById("lichelp").classList.add('is-danger');
      validlic = false;
    }
    else {
      document.getElementById("lichelp").display = 'none';
      validlic = true;
    }
  }

  document.getElementById("btnsubmit").disabled = !(validname && validinst && validemail && validlic); 
  
  
  // Dropdowns

  var $metalinks = getAll('#meta a');

  if ($metalinks.length > 0) {
    $metalinks.forEach(function ($el) {
      $el.addEventListener('click', function (event) {
        event.preventDefault();
        var target = $el.getAttribute('href');
        var $target = document.getElementById(target.substring(1));
        $target.scrollIntoView(true);
        // window.history.replaceState(null, document.title, `${window.location.origin}${window.location.pathname}${target}`);
        return false;
      });
    });
  }

  // Dropdowns

  var $dropdowns = getAll('.dropdown:not(.is-hoverable)');

  if ($dropdowns.length > 0) {
    $dropdowns.forEach(function ($el) {
      $el.addEventListener('click', function (event) {
        event.stopPropagation();
        $el.classList.toggle('is-active');
      });
    });

    document.addEventListener('click', function (event) {
      closeDropdowns();
    });
  }

  function closeDropdowns() {
    $dropdowns.forEach(function ($el) {
      $el.classList.remove('is-active');
    });
  }

  // Toggles

  var $burgers = getAll('.burger');

  if ($burgers.length > 0) {
    $burgers.forEach(function ($el) {
      $el.addEventListener('click', function () {
        var target = $el.dataset.target;
        var $target = document.getElementById(target);
        $el.classList.toggle('is-active');
        $target.classList.toggle('is-active');
      });
    });
  }

  // Modals

  var $html = document.documentElement;
  var $modals = getAll('.modal');
  var $modalButtons = getAll('.modal-button');
  var $modalCloses = getAll('.modal-background, .modal-close, .modal-card-head .delete, .modal-card-foot .button');

  if ($modalButtons.length > 0) {
    $modalButtons.forEach(function ($el) {
      $el.addEventListener('click', function () {
        var target = $el.dataset.target;
        var $target = document.getElementById(target);
        $html.classList.add('is-clipped');
        $target.classList.add('is-active');
      });
    });
  }

  if ($modalCloses.length > 0) {
    $modalCloses.forEach(function ($el) {
      $el.addEventListener('click', function () {
        closeModals();
      });
    });
  }

  document.addEventListener('keydown', function (event) {
    var e = event || window.event;
    if (e.keyCode === 27) {
      closeModals();
      closeDropdowns();
    }
  });

  function closeModals() {
    $html.classList.remove('is-clipped');
    $modals.forEach(function ($el) {
      $el.classList.remove('is-active');
    });
  }

  
  // Functions

  function getAll(selector) {
    return Array.prototype.slice.call(document.querySelectorAll(selector), 0);
  }

  var latestKnownScrollY = 0;
  var ticking = false;

  function scrollUpdate() {
    ticking = false;
    // do stuff
  }

  function onScroll() {
    latestKnownScrollY = window.scrollY;
    scrollRequestTick();
  }

  function scrollRequestTick() {
    if (!ticking) {
      requestAnimationFrame(scrollUpdate);
    }
    ticking = true;
  }

  window.addEventListener('scroll', onScroll, false);
});