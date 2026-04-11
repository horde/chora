/**
 * Revision log javascript.
 */

var Chora_RevLog = {

    selected: null,

    highlight: function()
    {
        var revlogBody = document.getElementById('revlog_body');
        if (revlogBody) {
            revlogBody.querySelectorAll('tr').forEach(function(tr) {
                tr.addEventListener('click', this.toggle.bind(this));
            }, this);
        }
    },

    toggle: function(e)
    {
        // Ignore clicks on links.
        var elt = e.target;
        if (elt.tagName.toUpperCase() != 'TR') {
            if (elt.tagName.toUpperCase() == 'A' &&
                elt.getAttribute('href')) {
                return;
            }
            elt = elt.closest('tr');
        }

        if (this.selected != null) {
            this.selected.classList.remove('selected');
            if (this.selected == elt) {
                this.selected = null;
                document.getElementById('revlog_body').classList.remove('selection');
                return;
            }
        }

        this.selected = elt;
        elt.classList.add('selected');
        document.getElementById('revlog_body').classList.add('selection');
    },

    sdiff: function(link)
    {
        link = document.getElementById(link);
        link.setAttribute('href', link.getAttribute('href').replace(/r1=([\d\.]+)/, 'r1=' + this.selected.id.substring(3)));
    }
};

document.addEventListener('DOMContentLoaded', Chora_RevLog.highlight.bind(Chora_RevLog));
