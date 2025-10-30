<!-- Controls section component -->
<div class="controls-section" x-show="!showRecords">
    @include('components.date-selector')
    @include('components.action-buttons')
</div>

<style>
    .controls-section {
        display: flex;
        gap: 15px;
        align-items: flex-end;
        margin-bottom: 20px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 12px;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    @media (max-width: 768px) {
        .controls-section {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>
