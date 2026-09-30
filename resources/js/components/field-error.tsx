export function FieldError({ message, id }: { message?: string; id?: string }) {
    if (!message) {
        return null;
    }

    return (
        <p id={id} className="text-sm text-destructive" role="alert">
            {message}
        </p>
    );
}
