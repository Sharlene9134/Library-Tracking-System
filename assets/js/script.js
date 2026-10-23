// OPEN ANY MODAL
document.querySelectorAll(".open-modal").forEach(btn => {
    btn.addEventListener("click", () => {
        const modalId = btn.getAttribute("data-modal");
        document.getElementById(modalId).style.display = "flex";
    });
});

// CLOSE USING X OR CLOSE BUTTON
document.querySelectorAll(".modal .close-btn, .modal .close-modal-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        btn.closest(".modal").style.display = "none";
    });
});

// CLOSE BY CLICKING OUTSIDE
window.addEventListener("click", e => {
    document.querySelectorAll(".modal").forEach(modal => {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    });
});

// ADD BOOK MODAL (legacy handlers kept for ids)
const legacyOpen = document.getElementById("openModal");
if (legacyOpen) {
    legacyOpen.onclick = () => {
        const m = document.getElementById("modal");
        if (m) m.style.display = "flex";
    };
}
const legacyClose = document.getElementById("closeBtn");
if (legacyClose) {
    legacyClose.onclick = () => {
        const m = document.getElementById("modal");
        if (m) m.style.display = "none";
    };
}

// FILL EDIT MODAL WITH DATA FROM BUTTON
document.querySelectorAll(".open-modal").forEach(btn => {
    btn.addEventListener("click", function () {

        if (this.getAttribute("data-modal") === "editModal") {

            document.getElementById("editTransaction_ID").value = this.getAttribute("data-id");
            document.getElementById("editBook_ID").value = this.getAttribute("data-book_id");
            document.getElementById("editBorrower_ID").value = this.getAttribute("data-borrower_id");
            document.getElementById("editBorrowed_date").value = this.getAttribute("data-borrowed_date");
            document.getElementById("editTime_Borrowed").value = this.getAttribute("data-time_borrowed");
            document.getElementById("editBorrowed_Staff_ID").value = this.getAttribute("data-borrowed_staff_id");
            document.getElementById("editDue_date").value = this.getAttribute("data-due_date");
            document.getElementById("editReturned_date").value = this.getAttribute("data-returned_date");
            document.getElementById("editTime_Returned").value = this.getAttribute("data-time_returned");
            document.getElementById("editReturned_Staff_ID").value = this.getAttribute("data-returned_staff_id");
        }
    });
});
