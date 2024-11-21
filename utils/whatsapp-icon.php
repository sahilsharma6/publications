<!-- WhatsApp Icon -->

<style>
    #whatsapp-icon {
        position: fixed;
        bottom: 20px;
        /* Distance from the bottom */
        right: 20px;
        /* Distance from the right */
        background-color: #25d366;
        /* WhatsApp color */
        padding: 8px;
        border-radius: 50%;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.15);
        }

        100% {
            transform: scale(1);
        }
    }

    #whatsapp-icon img {
        width: 40px;
        /* Set the size of the WhatsApp icon */
        height: 40px;
        /* Set the size of the WhatsApp icon */
    }

    /* #whatsapp-icon:hover {
        transform: rotate(360deg);
    } */
</style>
<a href="https://wa.me/+919752747384" target="_blank" id="whatsapp-icon">
    <img src="uploads/assets/WhatsApp.svg" alt="WhatsApp" />
</a>