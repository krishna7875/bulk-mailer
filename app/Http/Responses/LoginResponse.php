public function toResponse($request)
{
    if (auth()->user()->role === 'super_admin') {
        return redirect()->route('dashboard');
    }

    if (auth()->user()->role === 'admin') {
        return redirect()->route('dashboard');
    }

    return redirect()->route('dashboard');
}
