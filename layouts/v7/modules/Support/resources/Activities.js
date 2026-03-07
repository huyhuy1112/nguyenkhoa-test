jQuery(function () {
  var table = jQuery("#SupportActivitiesTable");
  if (!table.length) {
    return;
  }

  table.find("tr[data-activity-type]").each(function () {
    var row = jQuery(this);
    var type = (row.attr("data-activity-type") || "").toLowerCase();
    var detailUrl = row.attr("data-detail-url");

    if (!detailUrl) {
      detailUrl = row.find('a[target="_blank"]').attr("href") || "";
      if (detailUrl) {
        row.attr("data-detail-url", detailUrl);
      }
    }

    if (detailUrl) {
      row.css("cursor", "pointer");
    }

    var tooltipText = "Activity type: " + (type || "unknown");
    row.attr("title", tooltipText);
    row.find(".sa-tag").attr("title", tooltipText);
  });

  // 1) Row hover highlight
  table.on("mouseenter", "tr[data-activity-type]", function () {
    jQuery(this).css("background-color", "#f5f7fa");
  });
  table.on("mouseleave", "tr[data-activity-type]", function () {
    jQuery(this).css("background-color", "");
  });

  // 2) Click row -> open detail page
  table.on("click", "tr[data-activity-type]", function (event) {
    var target = jQuery(event.target);
    if (target.closest("a, button, input, select, textarea").length) {
      return;
    }
    var detailUrl = jQuery(this).attr("data-detail-url");
    if (detailUrl) {
      window.location.href = detailUrl;
    }
  });
});

