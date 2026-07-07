# Login Info Backup (2026-07-07)

このファイルは、ログイン画面から削除した表示情報のバックアップです。
公開ディレクトリ直下ではなく、運用ドキュメントとして保管します。

## 削除前に画面で表示していた内容

- テストアカウント自動入力ボタン
- 学生: username `student` / password `1234`
- 販売者: username `seller` / password `seller`
- 管理者: username `admin` / password `admin`

## 削除前に画面から実行可能だった接続テスト

- `/api/auth/login` 接続テストボタン
- 固定値での送信:
  - username: `student`
  - password: `1234`

## 取り扱い注意

- この情報は本番ページに再表示しないこと。
- 共有時は必要最小限の担当者に限定すること。
- テスト用資格情報は必ずローテーションまたは無効化すること。
