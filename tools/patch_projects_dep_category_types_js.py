#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from __future__ import annotations
from pathlib import Path
from datetime import datetime
import re

ROOT = Path(".").resolve()
JS = ROOT / "assets/js/admin/projects-area.js"

def read(p: Path) -> str:
    return p.read_text(encoding="utf-8", errors="replace")

def write(p: Path, s: str) -> None:
    p.write_text(s, encoding="utf-8")

def backup(p: Path) -> Path:
    ts = datetime.now().strftime("%Y%m%d-%H%M%S")
    b = p.with_suffix(p.suffix + f".bak.{ts}")
    b.write_text(read(p), encoding="utf-8")
    return b

INJECT = r"""
/* === Dependent: Category -> Property Types (Projects Basics) === */
(function(){
  function norm(s){ return (s||"").replace(/\s+/g," ").trim().toLowerCase(); }

  function findFieldRowByLabel(targetText){
    targetText = norm(targetText);
    var labels = Array.from(document.querySelectorAll("label, .carbon-field__label, .carbon-fields--field__label, th label, th, .field-label"));
    for (var i=0;i<labels.length;i++){
      var t = norm(labels[i].textContent || "");
      if(!t) continue;
      if(t.indexOf(targetText)!==-1){
        return labels[i].closest(".carbon-field") ||
               labels[i].closest(".carbon-field-row") ||
               labels[i].closest(".carbon-fields--field") ||
               labels[i].closest(".carbon-container__field") ||
               labels[i].closest("tr") ||
               labels[i].parentElement;
      }
    }
    return null;
  }

  function getSelect(row){
    if(!row) return null;
    return row.querySelector("select");
  }

  function getIndex(){
    // localized earlier in PHP enqueue (you already added localization for basics)
    // Try multiple global names (safe):
    var g = window.AqarandProjectBasics || window.aqarandProjectBasics || window.AQARAND_PROJECT_BASICS || null;
    if(!g) return null;
    return g.propertyTypesIndex || g.property_types_index || g.typesIndex || null;
  }

  function rebuildOptions(sel, allowedIds, keepSelected){
    var all = sel.__aqarand_all_options;
    if(!all){
      all = [];
      Array.from(sel.options).forEach(function(o){
        all.push({value:o.value, text:o.text, selected:o.selected});
      });
      sel.__aqarand_all_options = all;
    }

    var prev = Array.from(sel.selectedOptions || []).map(function(o){ return o.value; });
    sel.innerHTML = "";

    var allowedSet = null;
    if(Array.isArray(allowedIds)) {
      allowedSet = {};
      allowedIds.forEach(function(x){ allowedSet[String(x)] = true; });
    }

    all.forEach(function(o){
      if(!o.value) return;
      if(allowedSet && !allowedSet[o.value]) return;
      var opt = document.createElement("option");
      opt.value = o.value;
      opt.textContent = o.text;
      sel.appendChild(opt);
    });

    // restore selection (only values still present)
    if(keepSelected){
      prev.forEach(function(v){
        var opt = sel.querySelector('option[value="'+CSS.escape(v)+'"]');
        if(opt) opt.selected = true;
      });
    }

    // fire change for Carbon/select2
    try { sel.dispatchEvent(new Event("change", {bubbles:true})); } catch(e){}
    if(window.jQuery && window.jQuery.fn && window.jQuery.fn.select2){
      try { window.jQuery(sel).trigger("change.select2"); } catch(e){}
    }
  }

  function applyFilter(){
    var idx = getIndex();
    if(!idx) return;

    // labels (AR/EN). We detect using contains.
    var rowCat = findFieldRowByLabel("category") || findFieldRowByLabel("التصنيف");
    var rowTypes = findFieldRowByLabel("property types") || findFieldRowByLabel("أنواع") || findFieldRowByLabel("نوع العقار");

    var catSel = getSelect(rowCat);
    var typeSel = getSelect(rowTypes);

    if(!catSel || !typeSel) return;

    var catId = String(catSel.value || "");

    // compute allowed ids
    var allowed = [];
    Object.keys(idx).forEach(function(typeId){
      var cats = (idx[typeId] && idx[typeId].categories) ? idx[typeId].categories : [];
      cats = Array.isArray(cats) ? cats.map(String) : [];
      if(catId && cats.indexOf(catId)!==-1) allowed.push(String(typeId));
    });

    // If no cat selected -> show all
    if(!catId){
      rebuildOptions(typeSel, null, true);
    } else {
      rebuildOptions(typeSel, allowed, true);
    }
  }

  function boot(){
    // wait until Carbon renders fields
    var tries=0;
    var t=setInterval(function(){
      tries++;
      var rowCat = findFieldRowByLabel("category") || findFieldRowByLabel("التصنيف");
      var rowTypes = findFieldRowByLabel("property types") || findFieldRowByLabel("أنواع") || findFieldRowByLabel("نوع العقار");
      var ok = rowCat && rowTypes && getSelect(rowCat) && getSelect(rowTypes);
      if(ok){
        clearInterval(t);
        var catSel = getSelect(rowCat);
        catSel.addEventListener("change", function(){ applyFilter(); });
        applyFilter();
      }
      if(tries>=40){ clearInterval(t); }
    }, 250);
  }

  if(document.readyState==="loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
"""

def main():
    src = read(JS)
    if "Dependent: Category -> Property Types" in src:
        print("[SKIP] Already patched:", JS)
        return

    b = backup(JS)
    # append safely at end
    out = src.rstrip() + "\n\n" + INJECT.strip() + "\n"
    write(JS, out)

    print("[OK] Patched:", JS)
    print("[OK] Backup :", b)
    print("[OK] Added dependent Category -> Property Types filtering logic.")
