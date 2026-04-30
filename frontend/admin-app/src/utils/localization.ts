export function Loc(name: string): string {
    let message = '';

    try {
        message = BX.Loc.getMessage(name);
    } catch (e) {
    }

    return message;
}