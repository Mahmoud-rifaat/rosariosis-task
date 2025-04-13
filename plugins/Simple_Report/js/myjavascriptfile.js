window.addEventListener("load", function () {
    const studentsMenu = document.getElementById("menu_Students");
    if (studentsMenu) {
        const newItem = document.createElement("li");
        newItem.innerHTML = "<a href=\"Modules.php?modname=Students/Report.php\">Simple Report</a>";
        studentsMenu.appendChild(newItem);
    }
});