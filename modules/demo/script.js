function initDemoForm() {
  $G("range1").addEvent("input", function() {
    $E("register_amount").value = this.value;
  });
  $G("register_amount").addEvent("change", function() {
    $E("range1").setValue(this.value);
  });
  $G("range1").addEvent("change", function() {
    document.title = this.value;
  });
  $G("range2").addEvent("input", function() {
    $E("register_phone").value = this.value;
  });
  $G("range3").addEvent("input", function() {
    $E("register_amount").value = this.value;
  });
  $G("range3").addEvent("change", function() {
    $E("register_phone").value = this.value;
  });
  $G("range4").addEvent("input", function() {
    $E("register_amount").value = this.value;
  });
  $G("range4").addEvent("change", function() {
    $E("register_phone").value = this.value;
  });
  $G('select_checkbox').addEvent('change', function() {
    var values = this.value;
    forEach($E('register_province').getElementsByTagName('input'), function() {
      this.checked = values.indexOf(this.value) > -1;
    });
  });
  $E('select_checkbox').value = [104, 105, 106];
  forEach($E('register_province').getElementsByTagName('input'), function() {
    callClick(this, function() {
      var chks = [];
      forEach($E('register_province').getElementsByTagName('input'), function() {
        if (this.checked) {
          chks.push(this.value);
        }
      });
      $E('select_checkbox').value = chks;
    });
  });
  forEach($E('register_permission').getElementsByTagName('input'), function() {
    callClick(this, function() {
      $E(this.value).disabled = !this.checked;
    });
  });
  /*
  initCalendarRange("register_from_date", "register_to_date");
  */
  var DateTimeChange = function() {
    $E('text_checkbox').value = this.id + '=' + this.value;
  };
  $G('register_from_date').addEvent('change', DateTimeChange);
  $G('register_from_time').addEvent('change', DateTimeChange);
  $G('register_to_date').addEvent('change', DateTimeChange);
  $G('register_min_date').addEvent('change', DateTimeChange);
}

function initDemoProvince() {
  new GMultiSelect(['provinceID', 'amphurID', 'districtID', 'zipcode'], {
    action: WEB_URL + "index.php/demo/model/province/get"
  });
}


function initDemoAutocomplete(prefix) {
  _prefix = prefix ? prefix + '_' : '';
  var o = {
    get: function() {
      var q = null,
        key = prefix ? this.id.replace(prefix + '_', '') : this.id,
        value = $E(this.id).value;
      if (value != "") {
        q = key + "=" + encodeURIComponent(value);
      }
      return q;
    },
    callBack: function() {
      $G(_prefix + "district").valid().value = this.district;
      $G(_prefix + "amphur").valid().value = this.amphur;
      $G(_prefix + "province").valid().value = this.province;
      $G(_prefix + "zipcode").valid().value = this.zipcode;
      $E(_prefix + "districtID").value = this.districtID;
      $E(_prefix + "amphurID").value = this.amphurID;
      $E(_prefix + "provinceID").value = this.provinceID;
    },
    onChanged: function() {
      $G(_prefix + "district").reset();
      $G(_prefix + "amphur").reset();
      $G(_prefix + "province").reset();
      $G(_prefix + "zipcode").reset();
      $E(_prefix + "districtID").value = 0;
      $E(_prefix + "amphurID").value = 0;
      $E(_prefix + "provinceID").value = 0;
    }
  };
  initAutoComplete(
    _prefix + "district",
    WEB_URL + "index.php/demo/model/autocomplete/district",
    "district,amphur,province",
    "location",
    o
  );
  initAutoComplete(
    _prefix + "amphur",
    WEB_URL + "index.php/demo/model/autocomplete/amphur",
    "district,amphur,province",
    "location",
    o
  );
  initAutoComplete(
    _prefix + "province",
    WEB_URL + "index.php/demo/model/autocomplete/province",
    "district,amphur,province",
    "location",
    o
  );
  initAutoComplete(
    _prefix + "zipcode",
    WEB_URL + "index.php/demo/model/autocomplete/zipcode",
    "district,amphur,province,zipcode",
    "location",
    o
  );
}

var doEventClick = function(d) {
  alert("id=" + this.id + "\nparams=" + d);
};

function initDemoSignature() {
  var signaturePad = new SignaturePad($E('signature-pad'), {
    penColor: '#00008b',
    onChanged: function() {
      $E('approve_clear').disabled = this.isEmpty();
      $E('approve_save').disabled = this.isEmpty();
      $E('signature').value = this.toDataURL('image/png');
    }
  });
  callClick('approve_clear', function() {
    signaturePad.clear();
  });
}
