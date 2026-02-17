function updateTreePreview() {
    const rows = document.querySelectorAll('.row');
    const display = document.getElementById('tree-display');
    if(!display) return;

    let html = '<div style="line-height: 1.6; font-size: 13px;">';
    
    rows.forEach((row, index) => {
        const lvl = row.querySelector('.level-select').value;
        const title = row.querySelector('.title-input').value || "...";
        const isLast = (index === rows.length - 1);
        
        let prefix = "";
        let icon = "📄";
        let color = "#adadad";

        if (lvl === "1") {
            icon = "🏠";
            color = "#ffca28";
            prefix = "";
        } else {
            let indent = "";
            if (lvl === "2") { 
                indent = "&nbsp;&nbsp;"; 
                icon = "📁"; 
                color = "#64b5f6";
            } else if (lvl === "3") { 
                indent = "&nbsp;&nbsp;┃&nbsp;&nbsp;"; 
                icon = "📂"; 
                color = "#81c784";
            } else { 
                indent = "&nbsp;&nbsp;┃&nbsp;&nbsp;&nbsp;&nbsp;"; 
                icon = "📝";
                color = "#9e9e9e";
            }
            
            const connector = isLast ? "┗&nbsp;" : "┣&nbsp;";
            prefix = `<span style="color: #555;">${indent}${connector}</span>`;
        }

        html += `<div style="color: ${color}; white-space: nowrap;">
                    ${prefix}${icon} ${title}
                 </div>`;
    });

    html += '</div>';
    display.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('input', (e) => {
        if(e.target.classList.contains('title-input')) updateTreePreview();
    });
    document.addEventListener('change', (e) => {
        if(e.target.classList.contains('level-select')) updateTreePreview();
    });

    const container = document.getElementById('inputs-container');
    if(container) {
        const observer = new MutationObserver(updateTreePreview);
        observer.observe(container, { childList: true, subtree: true });
    }
    updateTreePreview();
});