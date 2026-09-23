@{
    # Copy this file to deploy.local.psd1 and keep the local file untracked.
    Host           = 'sftp.example.test'
    UserName       = 'restricted-deployment-account'
    Port           = 222
    RemotePath     = '.'
    KnownHostsFile = '~/.ssh/known_hosts'
}
