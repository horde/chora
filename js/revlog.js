var revlog_selected=null;var isMSIE=false;function revlog_highlight(){var a=$("revlog_body");$A(a.getElementsByTagName("TR")).each(function(b){if(isMSIE){Event.observe(b,"mouseover",(function(){Element.addClassName(this,"hover")}).bind(b));Event.observe(b,"mouseout",(function(){Element.removeClassName(this,"hover")}).bind(b))}Event.observe(b,"click",revlog_toggle.bindAsEventListener(b))})}function revlog_toggle(b){var a=Event.element(b);while(a!=this){if(a.tagName.toUpperCase()=="A"&&a.getAttribute("href")){return}a=a.parentNode}if(revlog_selected!=null){Element.removeClassName(revlog_selected,"selected");if(revlog_selected==this){revlog_selected=null;Element.removeClassName("revlog_body","selection");return}}revlog_selected=this;Element.addClassName(this,"selected");Element.addClassName("revlog_body","selection")}function revlog_sdiff(a){a.href=a.href.replace(/r1=([\d\.]+)/,"r1="+revlog_selected.id.substring(3))}Event.observe(window,"load",revlog_highlight);