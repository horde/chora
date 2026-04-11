/**
 * Chora annotate.php javascript code.
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

var Chora_Annotate = {
    showLog: function(e) {
        var elt = e.target.closest('span.logdisplay'), rev, newelt, tr;
        if (!elt) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        tr = elt.closest('tr');
        if (elt.dataset.expanded === 'true') {
            var nextTr = tr.nextElementSibling;
            if (nextTr) {
                nextTr.remove();
            }
            elt.dataset.expanded = 'false';
        } else {
            rev = elt.getAttribute('rev');
            newelt = document.createElement('td');
            newelt.setAttribute('colspan', '6');
            newelt.innerHTML = Chora.loading_text;
            var newRow = document.createElement('tr');
            newRow.className = 'logentry';
            newRow.appendChild(newelt);
            tr.after(newRow);
            elt.dataset.expanded = 'true';
            fetch(Chora.ANNOTATE_URL + '=' + rev)
                .then(function(r) { return r.text(); })
                .then(function(html) { newelt.innerHTML = html; });
        }
    }
};

document.addEventListener('click', Chora_Annotate.showLog.bind(Chora_Annotate));
