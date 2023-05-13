## [Unreleased]

## [0.2.0] - 2023-05-13
  * Added: getRequestInfo() methods to all endpoints, which reports timings and usage
  * Added: Download location to parser exception messages
  * Fixed: Bug in Competition parser to allow missing pilot names
  * Fixed: Bug in date format to allow 24 hour times
  * Fixed: Bug in user-agent handling and improved usage
  * Added: New 'updated' property to meta data
  * Fixed: Handling inconsistent Competitions html output
  * Added: System::toJson() method for json output
  * Added: getBatch() methods to all endpoints

## [0.1.1] - 2023-04-18
  * Fixed: Normalized downloader error messages

## [0.1.0] - 2023-04-17
  * Added: Initial release

[Unreleased]: https://github.com/johnstevenson/wprs-api/compare/0.2.0...HEAD
[0.2.0]: https://github.com/johnstevenson/wprs-api/compare/0.1.1...0.2.0
[0.1.1]: https://github.com/johnstevenson/wprs-api/compare/0.1.0...0.1.1
