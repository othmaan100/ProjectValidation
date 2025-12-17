// Form Validation Example
document.addEventListener("DOMContentLoaded", function () {
    const forms = document.querySelectorAll("form");

    forms.forEach((form) => {
        form.addEventListener("submit", function (event) {
            let valid = true;

            // Check only required fields
            form.querySelectorAll("input[required], textarea[required], select[required]").forEach((input) => {
                if (!input.value.trim()) {
                    valid = false;
                    input.style.borderColor = "red";
                } else {
                    input.style.borderColor = "#ccc";
                }
            });

            if (!valid) {
                event.preventDefault();
                alert("Please fill out all required fields.");
            }
        });
    });
});

// AJAX Example for Dynamic Content
function fetchTopicStatus() {
    fetch("api/get_topic_status.php")
        .then((response) => response.json())
        .then((data) => {
            const statusDiv = document.getElementById("topic-status");
            if (data.status === "approved") {
                statusDiv.innerHTML = `<p>Your topic has been <strong>approved</strong>!</p>`;
            } else if (data.status === "rejected") {
                statusDiv.innerHTML = `<p>Your topic has been <strong>rejected</strong>. Please submit a new topic.</p>`;
            } else {
                statusDiv.innerHTML = `<p>Your topic is still <strong>pending</strong>.</p>`;
            }
        })
        .catch((error) => console.error("Error fetching topic status:", error));
}

// Call the function on page load
document.addEventListener("DOMContentLoaded", fetchTopicStatus);