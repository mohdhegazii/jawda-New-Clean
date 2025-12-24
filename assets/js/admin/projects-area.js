(function () {
  var FACTOR = 4200.83;

  function closestRow(el) {
    if (!el) return null;
    return el.closest('tr') ||
           el.closest('.carbon-field') ||
           el.closest('.carbon-field-row') ||
           el.closest('.carbon-fields--field') ||
           el.closest('.carbon-container__field') ||
           el.parentElement;
  }

  function commonParent(a, b) {
    if (!a || !b) return null;
    var p = a.parentElement;
    while (p) {
      if (p.contains(b)) return p;
      p = p.parentElement;
    }
    return null;
  }

  function forceAreaUnitSameRow(areaEl, unitEl) {
    var rowA = closestRow(areaEl);
    var rowU = closestRow(unitEl);
    if (!rowA || !rowU) return;

    var parent = commonParent(rowA, rowU) || rowA.parentElement;
    if (!parent) return;

    // Make parent flex (works even if it's TBODY / DIV)
    parent.style.display = 'flex';
    parent.style.flexWrap = 'nowrap';
    parent.style.gap = '10px';
    parent.style.alignItems = 'flex-start';

    // TR can't be flex items unless blockified
    rowA.style.display = 'block';
    rowU.style.display = 'block';

    rowA.style.flex = '0 0 80%';
    rowA.style.maxWidth = '80%';

    rowU.style.flex = '0 0 20%';
    rowU.style.maxWidth = '20%';

    // Controls full width
    var aCtrl = rowA.querySelector('input, select, textarea');
    if (aCtrl) { aCtrl.style.width = '100%'; aCtrl.style.maxWidth = '100%'; }

    var uCtrl = rowU.querySelector('select, input, textarea');
    if (uCtrl) { uCtrl.style.width = '100%'; uCtrl.style.maxWidth = '100%'; }
  }

  function qs(sel, root) { return (root || document).querySelector(sel); }

  /* === Remove unused Carbon rows (Unit label-only + cf-html hidden row) === */
  function removeUnitAndHtmlRows() {
    // 1) Remove Unit label-only row
    var unitLabel = document.querySelector('.cf-field.cf-select .cf-field__label');
    if (unitLabel && unitLabel.textContent.trim() === 'Unit') {
      var row = unitLabel.closest('.cf-field');
      if (row) row.remove();
    }

    // 2) Remove cf-html row that only contains hidden area inputs
    var htmlRows = document.querySelectorAll('.cf-field.cf-html');
    htmlRows.forEach(function(row){
      if (row.querySelector('#jawda_area_m2') && row.querySelector('#jawda_area_acres')) {
        row.remove();
      }
    });
  }


  function closestRow(el) {
    if (!el) return null;
    return el.closest('tr') ||
           el.closest('.carbon-field') ||
           el.closest('.carbon-field-row') ||
           el.closest('.carbon-fields--field') ||
           el.closest('.carbon-container__field') ||
           el.parentElement;
  }

  function wrapAreaUnitRows(areaEl, unitEl) {
    var rowA = closestRow(areaEl);
    var rowU = closestRow(unitEl);
    if (!rowA || !rowU) return;

    // avoid double-wrapping
    var existing = rowA.closest('.jawda-areaunit-wrap') || rowU.closest('.jawda-areaunit-wrap');
    if (existing) {
      // still enforce widths inside wrapper
      existing.style.display = 'flex';
      existing.style.flexWrap = 'nowrap';
      existing.style.gap = '10px';
      existing.style.alignItems = 'flex-start';

      rowA.style.flex = '0 0 80%';
      rowA.style.maxWidth = '80%';
      rowU.style.flex = '0 0 20%';
      rowU.style.maxWidth = '20%';
      rowA.style.display = 'block';
      rowU.style.display = 'block';
      return;
    }

    var parent = rowA.parentElement;
    if (!parent) return;

    // create wrapper just for the 2 rows
    var wrap = document.createElement('div');
    wrap.className = 'jawda-areaunit-wrap';
    wrap.style.display = 'flex';
    wrap.style.flexWrap = 'nowrap';
    wrap.style.gap = '10px';
    wrap.style.alignItems = 'flex-start';

    // insert wrapper before rowA, then move rowA + rowU into it
    parent.insertBefore(wrap, rowA);
    wrap.appendChild(rowA);

    // if rowU was before rowA originally, moving rowA changed DOM; just append rowU now
    wrap.appendChild(rowU);

    // blockify the rows (works for TR or div-like rows)
    rowA.style.display = 'block';
    rowU.style.display = 'block';

    rowA.style.flex = '0 0 80%';
    rowA.style.maxWidth = '80%';

    rowU.style.flex = '0 0 20%';
    rowU.style.maxWidth = '20%';

    // controls full width inside columns
    var aCtrl = rowA.querySelector('input, select, textarea');
    if (aCtrl) { aCtrl.style.width = '100%'; aCtrl.style.maxWidth = '100%'; }

    var uCtrl = rowU.querySelector('select, input, textarea');
    if (uCtrl) { uCtrl.style.width = '100%'; uCtrl.style.maxWidth = '100%'; }
  }


  function getAreaInput() {
    return qs('input[name*="jawda_project_total_area_value"], input[id*="jawda_project_total_area_value"]');
  }
  function getUnitSelect() {
    return qs('select[name*="jawda_project_total_area_unit"], select[id*="jawda_project_total_area_unit"]');
  }
  function getHiddenM2() { return qs('#jawda_area_m2'); }
  function getHiddenAcres() { return qs('#jawda_area_acres'); }

  function toNum(v) {
    v = ("" + (v ?? "")).trim();
    if (!v) return null;
    var n = parseFloat(v);
    return isNaN(n) ? null : n;
  }
  function round(n, dec) {
    var p = Math.pow(10, dec);
    return Math.round(n * p) / p;
  }

  function normalizeUnit(v) {
    v = (v || "").toLowerCase();
    if (v === "m2" || v.indexOf("m²") !== -1) return "m2";
    if (v === "acres" || v.indexOf("feddan") !== -1 || v.indexOf("فدان") !== -1) return "acres";
    // our stored values are exactly: acres / m2
    if (v === "acres") return "acres";
    return v === "m2" ? "m2" : "acres";
  }

  function boot() {
    var inp = getAreaInput();
    var sel = getUnitSelect();
    var hidM2 = getHiddenM2();
    var hidA = getHiddenAcres();

    if (!inp || !sel || !hidM2 || !hidA) return false;

    

    

    

    
    moveUnitIntoAreaRow(inp, sel);
removeUnitAndHtmlRows();
wrapAreaUnitRows(inp, sel);
// forceAreaUnitSameRow(inp, sel); // disabled (wrap only area+unit)
var lastUnit = normalizeUnit(sel.value);
    var lock = false;

    function setHiddenFromShown() {
      if (lock) return;
      lock = true;

      var v = toNum(inp.value);
      var u = normalizeUnit(sel.value);

      if (v === null) {
        hidM2.value = "";
        hidA.value = "";
        lock = false;
        return;
      }

      if (u === "acres") {
        hidA.value = round(v, 4);
        hidM2.value = round(v * FACTOR, 2);
      } else {
        hidM2.value = round(v, 2);
        hidA.value = round(v / FACTOR, 4);
      }

      lock = false;
    }

    function convertShown(oldU, newU) {
      if (lock) return;
      lock = true;

      var v = toNum(inp.value);
      if (v === null) {
        lock = false;
        setHiddenFromShown();
        return;
      }

      if (oldU !== newU) {
        if (oldU === "acres" && newU === "m2") {
          inp.value = round(v * FACTOR, 2);
        } else if (oldU === "m2" && newU === "acres") {
          inp.value = round(v / FACTOR, 4);
        }
      }

      lock = false;
      setHiddenFromShown();
    }

    // typing -> keep hidden synced
    inp.addEventListener("input", setHiddenFromShown);

    // native change (may not fire with some UI, but keep it)
    sel.addEventListener("change", function () {
      var cur = normalizeUnit(sel.value);
      if (cur !== lastUnit) {
        convertShown(lastUnit, cur);
        lastUnit = cur;
      }
    });

    // Poller: guaranteed to detect changes even if select UI is replaced
    var ticks = 0;
    var poll = setInterval(function () {
      ticks++;

      // re-acquire if DOM replaced
      if (!document.contains(inp)) inp = getAreaInput() || inp;
      if (!document.contains(sel)) sel = getUnitSelect() || sel;

      if (!inp || !sel) {
        if (ticks > 200) clearInterval(poll);
        return;
      }

      var cur = normalizeUnit(sel.value);
      if (cur !== lastUnit) {
        convertShown(lastUnit, cur);
        lastUnit = cur;
      }

      if (ticks > 1200) clearInterval(poll);
    }, 200);

    // initial
    setHiddenFromShown();
    return true;
  }

  // Carbon/WP admin may render async -> retry boot
  var tries = 0;
  var t = setInterval(function () {
    tries++;
    if (boot()) clearInterval(t);
    if (tries > 80) clearInterval(t);
  }, 250);
})();

function qs(sel, root){ return (root || document).querySelector(sel); }

  /* === Basics data: dependent dropdowns (Category -> Property Types) === */
  function bootCategoryTypesDependent() {
    var catsSel = document.querySelector('select[name*="_hegzz_project_category_id"], select[id*="_hegzz_project_category_id"]');
    var typesSel = document.querySelector('select[multiple][name*="_hegzz_project_property_type_ids"], select[multiple][id*="_hegzz_project_property_type_ids"]');

    if (!catsSel || !typesSel) return false;

    var data = (window.AQARAND_PROJECTS_LOOKUPS || {});
    var typesIndex = data.property_types || {};

    function getSelected() {
      return Array.from(typesSel.selectedOptions || []).map(function(o){ return o.value; });
    }

    function rebuild(catId) {
      var selected = new Set(getSelected());
      var options = [];

      Object.keys(typesIndex).forEach(function(id){
        var row = typesIndex[id] || {};
        var label = row.label || id;
        var cats = row.categories || [];
        var catInt = parseInt(catId,10);
        var ok = !catId || cats.indexOf(catInt) !== -1 || cats.indexOf(String(catInt)) !== -1;
        if (ok) options.push({id:id, label:label});
      });

      options.sort(function(a,b){ return (a.label||'').localeCompare(b.label||''); });
      // If select2 is used, destroy BEFORE rebuilding options
      try {
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
          var $t = window.jQuery(typesSel);
          if ($t.hasClass('select2-hidden-accessible')) {
            $t.select2('destroy');
          }
        }
      } catch(e) {}

      typesSel.innerHTML = '';
      options.forEach(function(o){
        var opt = document.createElement('option');
        opt.value = o.id;
        opt.textContent = o.label;
        if (selected.has(o.id)) opt.selected = true;
        typesSel.appendChild(opt);
      });

      try {
        if (window.jQuery) {
          window.setTimeout(function(){
            try {
              var $t2 = window.jQuery(typesSel);
              if (window.jQuery.fn && window.jQuery.fn.select2) {
                if (!$t2.hasClass('select2-hidden-accessible')) {
                  try { $t2.select2(); } catch(e) {}
                }
              }
              $t2.trigger('change');
            } catch(e) {}
          }, 0);
        }
      } catch(e) {}
    }

    rebuild(catsSel.value);
    catsSel.addEventListener('change', function(){ rebuild(catsSel.value); });

    return true;
  }

  // retry because Carbon renders async
  (function(){
    var tries=0;
    var t=setInterval(function(){
      tries++;
      if (bootCategoryTypesDependent()) clearInterval(t);
      if (tries>80) clearInterval(t);
    }, 250);
  })();

// === UI: Put Total area + Unit in one row (80/20) + hide Unit label/row + hide HTML row ===
function moveUnitIntoAreaRow(inp, sel){
  try{
    if(!inp || !sel) return;

    var rowA = inp.closest('.carbon-field, .carbon-field-row, .carbon-fields--field, tr') || inp.parentElement;
    var rowU = sel.closest('.carbon-field, .carbon-field-row, .carbon-fields--field, tr') || sel.parentElement;
    if(!rowA || !rowU) return;

    // area body
    var bodyA = rowA.querySelector('.carbon-field__body, .cf-field__body, td') || rowA;

    // build wrapper for 80/20
    var wrap = bodyA.querySelector('.jawda-area-unit-inline');
    if(!wrap){
      wrap = document.createElement('div');
      wrap.className = 'jawda-area-unit-inline';
      wrap.style.display = 'flex';
      wrap.style.gap = '10px';
      wrap.style.alignItems = 'flex-end';
      wrap.style.width = '100%';
      // move existing controls into wrap (only control area, not labels)
      // keep bodyA clean
      while(bodyA.firstChild){ wrap.appendChild(bodyA.firstChild); }
      bodyA.appendChild(wrap);
    }

    // ensure input takes 80%
    inp.style.flex = '1 1 80%';
    inp.style.width = '100%';
    inp.style.maxWidth = '100%';

    // move select control from Unit row into wrap
    var selControl = sel.closest('.carbon-field__control, .cf-field__body, td, div') || sel.parentElement;
    if(selControl && selControl.parentElement !== wrap){
      // style select 20%
      sel.style.flex = '0 0 20%';
      sel.style.width = '100%';
      sel.style.maxWidth = '100%';
      wrap.appendChild(selControl);
    }

    // hide the Unit row completely (label + line)
    rowU.style.display = 'none';

    // hide the HTML field row (jawda_project_total_area_js)
    // try multiple selectors because Carbon markup differs by version
    var jsField =
      document.querySelector('[data-carbon-field-id="jawda_project_total_area_js"]') ||
      document.querySelector('[name="jawda_project_total_area_js"]') ||
      document.getElementById('jawda_project_total_area_js');

    if(jsField){
      var jsRow = jsField.closest('.carbon-field, .carbon-field-row, .carbon-fields--field, tr');
      if(jsRow) jsRow.style.display = 'none';
    }
  }catch(e){}
}
