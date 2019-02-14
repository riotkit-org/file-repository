
from ..entity.definition import BackupDefinition
from ..service.client import FileRepositoryClient
from ..service.pipefactory import PipeFactory
from ..exceptions import ReadWriteException
from ..result import CommandExecutionResult
from logging import Logger
import string
import random
import subprocess
from shutil import copyfileobj


class BackupHandler:
    """ Manages the process of backup and restore, interacts with different sources of backup data using adapters """

    _client = None        # type: FileRepositoryClient
    _pipe_factory = None  # type: PipeFactory
    _logger = None        # type: Logger
    _definition = None

    def __init__(self,
                 _client: FileRepositoryClient,
                 _pipe_factory: PipeFactory,
                 _logger: Logger,
                 _definition: BackupDefinition):
        self._client = _client
        self._pipe_factory = _pipe_factory
        self._logger = _logger
        self._definition = _definition

    def perform_backup(self):
        self._validate()

        response = self._read()

        if response.return_code != 0 and response.return_code is not None:
            raise ReadWriteException('Backup source read error, use --debug and retry to investigate')

        return self._client.send(response.stdout, self._get_definition())

    def perform_restore(self, version: str):
        response = self._write(
            self._read_from_storage(version)
        )

        response.process.wait()
        self._logger.info('Waiting for process to finish')

        if response.return_code is not None and response.return_code > 0:
            raise ReadWriteException('Cannot write files to disk while restoring from backup. Errors: '
                                     + str(response.stderr.read().decode('utf-8')))

        self._logger.info('No errors found, sending success information')

        return '{"status": "OK"}'

    def close(self):
        self._logger.info('Finishing the process')
        self._close()

    def _get_definition(self) -> BackupDefinition:
        return self._definition

    def _execute_command(self, command: str, stdin=None) -> CommandExecutionResult:
        """
        Executes a command on local machine, returning stdout as a stream, and streaming in the stdin (optionally)
        """

        self._logger.debug('shell(' + command + ')')

        process = subprocess.Popen(command,
                                   stdout=subprocess.PIPE,
                                   stderr=subprocess.PIPE,
                                   stdin=subprocess.PIPE if stdin else None,
                                   shell=True)

        if stdin:
            self._logger.info('Copying stdin to process')

            try:
                copyfileobj(stdin, process.stdin)
            except BrokenPipeError:
                raise ReadWriteException(
                    'Cannot write to process, broken pipe occurred, probably a tar process died. '
                    + str(process.stdin.read()) + str(process.stderr.read())
                )

            process.stdin.close()

        return CommandExecutionResult(process.stdout, process.stderr, process.returncode, process)

    def _validate(self):
        raise Exception('_validate() not implemented for handler')

    def _read(self) -> CommandExecutionResult:
        """ TAR output or file stream buffered from ANY source for example """
        raise Exception('_read() not implemented for handler')

    def _write(self, stream) -> CommandExecutionResult:
        """ A file stream or tar output be written into the storage. May be OpenSSL encoded, depends on definition """
        raise Exception('_write() not implemented for handler')

    def _read_from_storage(self, version: str):
        return self._client.fetch(version, self._get_definition())

    def _close(self):
        pass

    @staticmethod
    def generate_id(size=6, chars=string.ascii_uppercase + string.digits):
        return ''.join(random.choice(chars) for _ in range(size))
