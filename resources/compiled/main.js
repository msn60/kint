"use strict";(()=>{function m(n){if(!(n instanceof Element))throw new Error("Invalid argument to dedupeElement()");let t=n.ownerDocument,e=E(n);for(let s of t.querySelectorAll(e))n!==s&&s.parentNode.removeChild(s)}function d(n){return n instanceof Element?n.ownerDocument.contains(n):!1}function E(n){if(!(n instanceof Element))throw new Error("Invalid argument to buildClassSelector()");return[n.nodeName,...n.classList].join(".")}function f(n){if(!(n instanceof Element))throw new Error("Invalid argument to selectText()");let t=n.ownerDocument,e=t.getSelection(),s=t.createRange();s.selectNodeContents(n),e.removeAllRanges(),e.addRange(s)}function I(n,t){let e;return function(...s){clearTimeout(e),e=setTimeout(function(){n(...s)},t)}}function x(n){if(!(n instanceof Element))throw new Error("Invalid argument to offsetTop()");return n.offsetTop+(n.offsetParent?x(n.offsetParent):0)}var u=class n{static#e=new Set;static toggleSearchBox(t,e){let s=t.querySelector(".kint-search"),i=t.parentNode;if(s)if(s.classList.toggle("kint-show",e)){if(s.focus(),s.select(),!n.#e.has(s)){let r=i.querySelectorAll("dl").length,o=200;r>1e4&&(o=700),s.addEventListener("keyup",I(n.#t.bind(null,s),o)),n.#e.add(s)}n.#t(s)}else i.classList.remove("kint-search-root")}static#t(t){let e=t.closest(".kint-parent")?.parentNode;if(e)if(t.classList.contains("kint-show")&&t.value.length){let s=e.dataset.lastSearch;if(e.classList.add("kint-search-root"),s!==t.value){e.dataset.lastSearch=t.value,e.classList.remove("kint-search-match");for(let i of e.querySelectorAll(".kint-search-match"))i.classList.remove("kint-search-match");n.#s(e,t.value.toUpperCase())}}else e.classList.remove("kint-search-root")}static#s(t,e){let s=t.cloneNode(!0);for(let c of s.querySelectorAll(".access-path"))c.remove();if(!s.textContent.toUpperCase().includes(e))return;t.classList.add("kint-search-match");let i=t.firstElementChild;for(;i&&i.tagName!=="DT";)i=i.nextElementSibling;if(!i)return;let r=a.getChildContainer(i);if(!r)return;let o,l;for(let c of r.children)c.tagName==="DL"?n.#s(c,e):c.tagName==="UL"&&(c.classList.contains("kint-tabs")?o=c.children:c.classList.contains("kint-tab-contents")&&(l=c.children));if(!(!o||o.length!==l?.length))for(let c=o.length;c--;){let k=!1,F=!1;o[c].textContent.toUpperCase().includes(e)&&(k=!0);let O=l[c].cloneNode(!0);for(let v of O.querySelectorAll(".access-path"))v.remove();if(O.textContent.toUpperCase().includes(e)&&(k=!0,F=!0),k&&o[c].classList.add("kint-search-match"),F)for(let v of l[c].children)v.tagName==="DL"&&n.#s(v,e)}}};var g=class{static sort(t,e){let s=t.dataset.kintTableSort,i=parseInt(s)===e?-1:1,r=t.tBodies[0];[...r.rows].sort(function(o,l){o=o.cells[e].textContent.trim().toLocaleLowerCase(),l=l.cells[e].textContent.trim().toLocaleLowerCase();let c=0;return!isNaN(o)&&!isNaN(l)?(o=parseFloat(o),l=parseFloat(l),c=o-l):isNaN(o)&&!isNaN(l)?c=1:isNaN(l)&&!isNaN(o)?c=-1:c=(""+o).localeCompare(""+l),c*i}).forEach(o=>r.appendChild(o)),i<0?t.dataset.kintTableSort=null:t.dataset.kintTableSort=e}};var a=class n{#e;#t;#s;constructor(t){if(!(t instanceof h))throw new Error("Invalid argument to Rich.constructor()");this.#e=t,this.#e.runOnInit(this.#i.bind(this));let e=new q(this,t);new b(this,t.window,e)}#i(){let t=this.#e.window.document;if(d(this.#t)||(this.#t=t.querySelector("style.kint-rich-style")),this.#t&&m(this.#t),t.querySelector(".kint-rich.kint-file")){this.setupFolder(t);let e=this.#s.querySelector("dd.kint-foldout"),s=Array.from(t.querySelectorAll(".kint-rich.kint-file"));for(let i of s)i.parentNode!==e&&e.appendChild(i);this.#s.classList.add("kint-show")}}addToFolder(t){let e=t.closest(".kint-rich");if(!e)throw new Error("Bad addToFolder");let s=this.#e.window.document;if(this.setupFolder(s),this.folder.contains(t))throw new Error("Bad addToFolder");let i=this.#s.querySelector("dd.kint-foldout"),r=t.closest(".kint-parent, .kint-rich"),o=Array.from(e.querySelectorAll(".kint-folder-trigger"));if(e===r||e.querySelectorAll(".kint-rich > dl").length===1){for(let l of o)l.remove();e.classList.add("kint-file"),i.insertBefore(e,i.firstChild)}else{let l=s.createElement("div");l.classList.add("kint-rich"),l.classList.add("kint-file"),l.appendChild(r.closest(".kint-rich > dl"));let c=e.lastElementChild;c.matches(".kint-rich > footer")&&l.appendChild(c.cloneNode(!0));for(let k of o)k.remove();i.insertBefore(l,i.firstChild)}n.toggle(this.#s.querySelector(".kint-parent"),!0)}setupFolder(t){if(this.#s)d(this.#s)||(this.#s=t.querySelector(".kint-rich.kint-folder"));else{let e=t.createElement("template");e.innerHTML='<div class="kint-rich kint-folder"><dl><dt class="kint-parent"><nav></nav>Kint</dt><dd class="kint-foldout"></dd></dl></div>',this.#s=e.content.firstChild,t.body.appendChild(this.#s)}}get folder(){return d(this.#s)||(this.#s=this.#e.window.document.querySelector(".kint-rich.kint-folder")),this.#s&&m(this.#s),this.#s}isFolderOpen(){let t=this.#s?.querySelector("dd.kint-foldout");if(t)return t.previousSibling.classList.contains("kint-show")}static getChildContainer(t){let e=t.nextElementSibling;for(;e&&!e.matches("dd");)e=e.nextElementSibling;return e}static toggle(t,e){let s=n.getChildContainer(t);s&&(e=t.classList.toggle("kint-show",e),n.#n(s,e))}static switchTab(t){t.parentNode.getElementsByClassName("kint-active-tab")[0].classList.remove("kint-active-tab"),t.classList.add("kint-active-tab");let e=t,s=0;for(;e=e.previousElementSibling;)s++;let i=t.parentNode.nextSibling.children;for(let r=i.length;r--;)r===s?(i[r].classList.add("kint-show"),n.#n(i[r],!0)):i[r].classList.remove("kint-show")}static toggleChildren(t,e){let s=n.getChildContainer(t);if(!s)return;e===void 0&&(e=t.classList.contains("kint-show"));let i=Array.from(s.getElementsByClassName("kint-parent"));for(let r of i)r.classList.toggle("kint-show",e)}static toggleAccessPath(t,e){let s=t.querySelector(".access-path");s?.classList.toggle("kint-show",e)&&f(s)}static#n(t,e){if(t.children.length===2&&t.lastElementChild.matches("ul.kint-tab-contents"))for(let s of t.lastElementChild.children)s.matches("li.kint-show")&&(t=s);if(t.children.length===1&&t.firstElementChild.matches("dl")){let s=t.firstElementChild.firstElementChild;s?.classList?.contains("kint-parent")&&n.toggle(s,e)}}},b=class{#e;#t;#s;#i=null;#n=null;#o=0;constructor(t,e,s){this.#e=t,this.#t=s,this.#s=e,this.#s.addEventListener("click",this.#a.bind(this),!0)}#r(){clearTimeout(this.#i),this.#i=setTimeout(this.#l.bind(this),250)}#l(){clearTimeout(this.#i),this.#i=null,this.#n=null,this.#o=0}#c(){let t=this.#n;if(!t.matches(".kint-parent > nav"))return;let e=t.parentNode;if(this.#o===1)a.toggleChildren(e),this.#t.onTreeChanged(),this.#r(),this.#o=2;else if(this.#o===2){this.#l();let s=e.classList.contains("kint-show"),i=this.#e.folder?.querySelector(".kint-parent"),r=Array.from(this.#s.document.getElementsByClassName("kint-parent"));for(let o of r)o!==i&&o.classList.toggle("kint-show",s);this.#t.onTreeChanged(),this.#t.scrollToFocus()}}#a(t){if(this.#o){this.#c();return}let e=t.target;if(!e.closest(".kint-rich"))return;if(e.tagName==="DFN"&&f(e),e.tagName==="TH"){t.ctrlKey||g.sort(e.closest("table"),e.cellIndex);return}if(e.tagName==="LI"&&e.parentNode.className==="kint-tabs"){if(e.className!=="kint-active-tab"){let i=e.closest("dl")?.querySelector(".kint-parent > nav")??e;a.switchTab(e),this.#t.onTreeChanged(),this.#t.setCursor(i)}return}let s=e.closest("dt");if(e.tagName==="NAV")e.parentNode.tagName==="FOOTER"?(this.#t.setCursor(e),e.parentNode.classList.toggle("kint-show")):s?.classList.contains("kint-parent")&&(a.toggle(s),this.#t.onTreeChanged(),this.#t.setCursor(e),this.#r(),this.#o=1,this.#n=e);else if(e.classList.contains("kint-access-path-trigger"))s&&a.toggleAccessPath(s);else if(e.classList.contains("kint-search-trigger"))s?.matches(".kint-rich > dl > dt.kint-parent")&&u.toggleSearchBox(s);else if(e.classList.contains("kint-folder-trigger")){if(s?.matches(".kint-rich > dl > dt.kint-parent"))this.#e.addToFolder(e),this.#t.onTreeChanged(),this.#t.setCursor(s.querySelector("nav")),this.#t.scrollToFocus();else if(e.parentNode.tagName==="FOOTER"){let i=e.closest(".kint-rich").querySelector(".kint-parent > nav, .kint-rich > footer > nav");this.#e.addToFolder(e),this.#t.onTreeChanged(),this.#t.setCursor(i),this.#t.scrollToFocus()}}else e.classList.contains("kint-search")||(e.tagName==="PRE"&&t.detail===3?f(e):e.closest(".kint-source")&&t.detail===3?f(e.closest(".kint-source")):e.classList.contains("access-path")?f(e):e.tagName!=="A"&&s?.classList.contains("kint-parent")&&(a.toggle(s),this.#t.onTreeChanged(),this.#t.setCursor(s.querySelector("nav"))))}},j=65,G=68,A=70,S=72,K=74,D=75,p=76,V=83,P=9,T=13,B=27,L=32,N=37,R=38,C=39,H=40,M=".kint-rich .kint-parent > nav, .kint-rich > footer > nav, .kint-rich .kint-tabs > li:not(.kint-active-tab)",q=class{#e=[];#t=0;#s=!1;#i;#n;constructor(t,e){this.#i=t,this.#n=e.window,this.#n.addEventListener("keydown",this.#c.bind(this),!0),e.runOnInit(this.onTreeChanged.bind(this))}scrollToFocus(){let t=this.#e[this.#t];if(!t)return;let e=this.#i.folder;if(t===e?.querySelector(".kint-parent > nav"))return;let s=x(t);if(this.#i.isFolderOpen()){let i=e.querySelector("dd.kint-foldout");i.scrollTo(0,s-i.clientHeight/2)}else this.#n.scrollTo(0,s-this.#n.innerHeight/2)}onTreeChanged(){let t=this.#e[this.#t];this.#e=[];let e=this.#i.folder,s=e?.querySelector(".kint-parent > nav"),i=this.#n.document;this.#i.isFolderOpen()&&(i=e,this.#e.push(s));let r=Array.from(i.querySelectorAll(M));for(let o of r)o.offsetParent!==null&&o!==s&&this.#e.push(o);if(s&&!this.#i.isFolderOpen()&&this.#e.push(s),this.#e.length===0){this.#s=!1,this.#r();return}t&&this.#e.indexOf(t)!==-1?this.#t=this.#e.indexOf(t):this.#r()}setCursor(t){if(this.#i.isFolderOpen()&&!this.#i.folder.contains(t)||!t.matches(M))return!1;let e=this.#e.indexOf(t);if(e===-1&&(this.onTreeChanged(),e=this.#e.indexOf(t)),e!==-1){if(e!==this.#t)return this.#t=e,this.#r(),!0;this.#e[e]?.classList.remove("kint-weak-focus")}else console.error("setCursor failed to find target in list",t),console.info("Please report this as a bug in Kint at https://github.com/kint-php/kint");return!1}#o(t){if(this.#e.length===0)return this.#t=0,null;for(this.#t+=t;this.#t<0;)this.#t+=this.#e.length;for(;this.#t>=this.#e.length;)this.#t-=this.#e.length;return this.#r(),this.#t}#r(){let t=this.#n.document.querySelector(".kint-focused");t&&(t.classList.remove("kint-focused"),t.classList.remove("kint-weak-focus")),this.#s&&this.#e[this.#t]?.classList.add("kint-focused")}#l(t){let e=t.closest(".kint-rich .kint-parent ~ dd")?.parentNode.querySelector(".kint-parent > nav");e&&(this.setCursor(e),this.scrollToFocus())}#c(t){if(t.keyCode===B&&t.target.matches(".kint-search")){t.target.blur(),this.#s&&this.#r();return}if(t.target!==this.#n.document.body||t.altKey||t.ctrlKey)return;if(t.keyCode===G){if(this.#s)this.#s=!1;else{if(this.#s=!0,this.onTreeChanged(),this.#e.length===0){this.#s=!1;return}this.scrollToFocus()}this.#r(),t.preventDefault();return}else if(t.keyCode===B){this.#s&&(this.#s=!1,this.#r(),t.preventDefault());return}else if(!this.#s)return;t.preventDefault(),d(this.#e[this.#t])||this.onTreeChanged();let e=this.#e[this.#t];if([P,R,D,H,K].includes(t.keyCode)){t.keyCode===P?this.#o(t.shiftKey?-1:1):t.keyCode===R||t.keyCode===D?this.#o(-1):(t.keyCode===H||t.keyCode===K)&&this.#o(1),this.scrollToFocus();return}if(e.tagName==="LI"&&[L,T,C,p,N,S].includes(t.keyCode)){t.keyCode===L||t.keyCode===T?(a.switchTab(e),this.onTreeChanged()):t.keyCode===C||t.keyCode===p?this.#o(1):(t.keyCode===N||t.keyCode===S)&&this.#o(-1),this.scrollToFocus();return}if(e.parentNode.tagName==="FOOTER"&&e.closest(".kint-rich")){if(t.keyCode===L||t.keyCode===T)e.parentNode.classList.toggle("kint-show");else if(t.keyCode===N||t.keyCode===S)if(e.parentNode.classList.contains("kint-show"))e.parentNode.classList.remove("kint-show");else{this.#l(e.closest(".kint-rich"));return}else if(t.keyCode===C||t.keyCode===p)e.parentNode.classList.add("kint-show");else if(t.keyCode===A&&!this.#i.isFolderOpen()&&e.matches(".kint-rich > footer > nav")){let i=e.closest(".kint-rich").querySelector(".kint-parent > nav, .kint-rich > footer > nav");this.#i.addToFolder(e),this.onTreeChanged(),this.setCursor(i),this.scrollToFocus()}return}let s=e.closest(".kint-parent");if(s){if(t.keyCode===j){a.toggleAccessPath(s);return}if(t.keyCode===A){!this.#i.isFolderOpen()&&s.matches(".kint-rich:not(.kint-folder) > dl > .kint-parent")&&(this.#i.addToFolder(e),this.onTreeChanged(),this.setCursor(e),this.scrollToFocus());return}if(t.keyCode===V){let i=s.closest(".kint-rich > dl")?.querySelector(".kint-search")?.closest(".kint-parent");if(i){e.classList.add("kint-weak-focus"),u.toggleSearchBox(i,!0);return}}if(t.keyCode===L||t.keyCode===T){a.toggle(s),this.onTreeChanged();return}if([C,p,N,S].includes(t.keyCode)){let i=s.classList.contains("kint-show");if(t.keyCode===C||t.keyCode===p){i&&a.toggleChildren(s,!0),a.toggle(s,!0),this.onTreeChanged();return}else if(i){a.toggleChildren(s,!1),a.toggle(s,!1),this.onTreeChanged();return}else{this.#l(s);return}}}}};var y=class{#e;#t;constructor(t){if(!(t instanceof h))throw new Error("Invalid argument to Plain.constructor()");this.#e=t.window,t.runOnInit(this.#s.bind(this))}#s(){d(this.#t)||(this.#t=this.#e.document.querySelector("style.kint-plain-style")),this.#t&&m(this.#t)}};var w=class{#e;constructor(t){if(!(t instanceof h))throw new Error("Invalid argument to Microtime.constructor()");this.#e=t.window,t.runOnInit(this.#t.bind(this))}#t(){let t={},e=this.#e.document.querySelectorAll("[data-kint-microtime-group]");for(let s of e){let i=s.querySelector(".kint-microtime-lap");if(!i)continue;let r=s.dataset.kintMicrotimeGroup,o=parseFloat(i.textContent),l=parseFloat(s.querySelector(".kint-microtime-avg").textContent);t[r]??={min:o,max:o,avg:l},t[r].min>o&&(t[r].min=o),t[r].max<o&&(t[r].max=o),t[r].avg=l}for(let s of e){let i=s.querySelector(".kint-microtime-lap");if(!i)continue;let r=parseFloat(i.textContent),o=t[s.dataset.kintMicrotimeGroup];if(s.querySelector(".kint-microtime-avg").textContent=o.avg,!(r===o.min&&r===o.max))if(s.classList.add("kint-microtime-js"),r>o.avg){let l=(r-o.avg)/(o.max-o.avg);i.style.background="hsl("+(40-40*l)+", 100%, 65%)"}else{let l=0;o.avg!==o.min&&(l=(o.avg-r)/(o.avg-o.min)),i.style.background="hsl("+(40+80*l)+", 100%, 65%)"}}}};var U=Symbol(),h=class n{static#e=null;#t;#s=[];#i=new Set;static init(t){return n.#e??=new n(t,U),n.#e.#n(),n.#e.runOnLoad(n.#r),n.#e}get window(){return this.#t}constructor(t,e){if(U!==e)throw new Error("Kint constructor is private. Use Kint.init()");if(!(t instanceof Window))throw new Error("Invalid argument to Kint.init()");this.#t=t,this.runOnInit(this.#o.bind(this)),new y(this),new a(this),new w(this)}runOnLoad(t){if(this.#t.document.readyState==="complete")try{t()}catch{}else this.#t.addEventListener("load",t)}runOnInit(t){this.#s.push(t)}#n(){this.#t.document.currentScript&&(this.#i.add(E(window.document.currentScript)),window.document.currentScript.remove())}#o(){for(let t of this.#i.keys())for(let e of this.#t.document.querySelectorAll(t))e.remove()}static#r(){for(let t of n.#e.#s)t()}};window.Kint||(window.Kint=h);window.Kint.init(window);})();
