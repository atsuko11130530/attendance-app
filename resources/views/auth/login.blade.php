<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
</head>

<body>

    <h1>ログイン</h1>

    {{ route('login') }}
    @csrf

    <div>
        <label>メールアドレス</label>
        <input type="email" name="email">
    </div>

    <br>

    <div>
        <label>パスワード</label>
        <input type="password" name="password">
    </div>

    <br>

    <button type="submit">
        ログイン
    </button>
    </form>

    <br>

    }}">
    会員登録はこちら
    </a>

</body>

</html>