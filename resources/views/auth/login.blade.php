<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingia | DukaNasi</title>

    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>

<div class="login-page">

    <section class="login-visual">
        <div class="brand">
            <span class="brand-icon">🛒</span>
            <span>Duka<span>Nasi</span></span>
        </div>

        <div class="visual-content">
            <h1>
                Biashara yako.<br>
                <span>Kiganjani mwako.</span>
            </h1>

            <p>
                Simamia mauzo, bidhaa na matumizi kwa urahisi—
                popote ulipo.
            </p>

            <div class="benefits">
                <div class="benefit">
                    <span>▥</span>
                    <strong>Mauzo ya kila siku</strong>
                </div>

                <div class="benefit">
                    <span>◇</span>
                    <strong>Stock kwa wakati halisi</strong>
                </div>

                <div class="benefit">
                    <span>▤</span>
                    <strong>Ripoti zinazoeleweka</strong>
                </div>
            </div>
        </div>

        <div class="testimonial">
            <div class="testimonial-avatar">A</div>

            <div>
                <p>
                    “DukaNasi imenisaidia kujua mauzo,
                    stock na matumizi kwa urahisi.”
                </p>
                <small>— Amina Said, Dar es Salaam</small>
            </div>
        </div>
    </section>

    <section class="login-section">

        <div class="language-switch">
            <span class="active">SW</span>
            <span>|</span>
            <span>EN</span>
        </div>

        <div class="login-card">

            <div class="login-logo">
                <span class="logo-icon">🛒</span>
                <span>Duka<span>Nasi</span></span>
            </div>

            <h2>Karibu tena</h2>

            <p class="login-subtitle">
                Ingia kuendelea na DukaNasi
            </p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="login">
                        Namba ya simu au barua pepe
                    </label>

                    <input
                        type="text"
                        id="login"
                        name="login"
                        value="{{ old('login') }}"
                        placeholder="Mfano: 0712 345 678 au jina@barua.co.tz"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Nenosiri</label>

                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Ingiza nenosiri lako"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword()"
                        >
                            ◉
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember">
                        <input type="checkbox" name="remember">
                        <span>Nikumbuke</span>
                    </label>

                    {{-- <a href="{{ route('password.request') }}">
                            Umesahau nenosiri?
                        </a> --}}
                </div>

                <button type="submit" class="login-button">
                    Ingia kwenye akaunti
                </button>
            </form>

            <div class="divider">
                <span></span>
                <small>au</small>
                <span></span>
            </div>

            <button type="button" class="google-button">
                <span class="google-icon">G</span>
                Ingia kwa kutumia Google
            </button>

            <p class="register-text">
                Huna akaunti?
               {{-- <a href="{{ route('register') }}">Anza bure</a> --}} 
            </p>
        </div>

        <footer>
            © {{ date('Y') }} DukaNasi
            <span>·</span>
            Faragha
            <span>·</span>
            Masharti
        </footer>

    </section>

</div>

<script>
    function togglePassword() {
        const password = document.getElementById('password');
        const button = document.querySelector('.password-toggle');

        if (password.type === 'password') {
            password.type = 'text';
            button.textContent = '◉';
        } else {
            password.type = 'password';
            button.textContent = '◌';
        }
    }
</script>

</body>
</html>