export default (iconOrMessage, title = null, message = null) => ({
    init() {
        const isLegacySingleArg = title === null && message === null;
        const resolvedIcon = isLegacySingleArg ? 'success' : iconOrMessage;
        const resolvedTitle = isLegacySingleArg ? 'System Update' : title;
        const resolvedHtml = isLegacySingleArg ? iconOrMessage : message;

        const confirmColor = resolvedIcon === 'error'
            ? 'var(--theme-bg-danger)'
            : 'var(--theme-bg-primary)';

        window.Swal.fire({
            icon: resolvedIcon,
            title: resolvedTitle,
            html: resolvedHtml,
            background: 'var(--theme-bg-panel)',
            color: 'var(--theme-text-p)',
            confirmButtonColor: confirmColor
        });
    }
});