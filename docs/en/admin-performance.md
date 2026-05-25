# Performance Monitoring

XooPress provides a performance monitoring dashboard that displays database query profiling and execution metrics.

## Accessing Performance Monitoring

Navigate to **Admin → Performance** (`/admin/performance`) to view performance data.

## Dashboard

The performance dashboard displays:

### Query Profiler
- **Total Queries** — Number of database queries executed during page loads
- **Slow Queries** — Queries that exceed the configured threshold
- **Query Time** — Total time spent executing database queries
- **Average Query Time** — Average execution time per query

### Execution Metrics
- **Execution Time** — Total page generation time
- **Peak Memory** — Maximum memory usage during page generation
- **Cache Hits/Misses** — Cache service performance statistics

### Database Queries
The profiler logs all SQL queries executed during the request lifecycle, including:
- The full SQL string with bound parameters
- Execution time for each query
- Slow queries highlighted in red
- Call stack information for identifying the source of queries

## Profiler Integration

The profiler is provided by the `Profiler` class (`app/Core/Profiler.php`), which:
- Automatically captures all database queries via the `Database` class
- Logs execution times and memory usage
- Highlights queries that exceed the slow query threshold
- Provides data to both the performance dashboard and the debug bar

## Debug Bar

An in-page debug toolbar is available when debug mode is enabled:

### Enabling the Debug Bar

Set debug mode in `config/app.local.php`:

```php
'debug' => true,
```

### Debug Bar Sections

- **Execution Time** — Total page generation time
- **Memory Usage** — Peak memory consumption
- **SQL Queries** — Number of queries and total time, click to expand details
- **Route Info** — Matched route, controller, and parameters
- **Request Data** — GET, POST, and server variables
- **Session Data** — Current session contents

The debug bar is rendered at the bottom of the page and is visible to users with admin capabilities.