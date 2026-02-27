<div class="overlay" id="deleteOverlay">
    <div class="modal">
        <div class="modal-icon-wrap">
            <i class='bx bx-trash'></i>
        </div>

        <h3>Delete Item?</h3>

        <p>
            Are you sure you want to delete
            <strong id="delItemName"></strong>?
            This action cannot be undone.
        </p>

        <form method="POST" id="deleteForm" class="modal-btns">
            <input type="hidden" name="delete_id" id="deleteIdField">

            <button type="button" class="btn-cancel-modal" onclick="closeModal()">
                Cancel
            </button>

            <button type="submit" class="btn-del-confirm" onclick="showSpinner()">
                <span class="spinner" id="delSpinner"></span>
                <span id="delBtnText">Delete</span>
            </button>
        </form>
    </div>
</div>