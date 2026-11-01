<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会員登録</title>

    @vite(['resources/css/auth.css'])
</head>

<body>

    <header class="header">
        <h1>COACHTECH</h1>
    </header>

    <main class="auth-container">

        <h2 class="auth-title">会員登録</h2>

        <form action="{{ route('register.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name">名前</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}">
            </div>

            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}">
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input
                    type="password"
                    id="password"
                    name="password">
            </div>

            <div class="form-group">
                <label for="password_confirmation">パスワード確認</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation">
            </div>

            <button type="submit" class="submit-button">
                登録する
            </button>
        </form>

        <div class="auth-link">
        </div>

        <div class="auth-link">
            <a href="{{ route('login') }}">
                ログインはこちら
            </a>
        </div>
    </main>

</body>

</html>