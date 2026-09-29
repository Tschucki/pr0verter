/**
 * Adapts a Zod 4 object schema to vee-validate 4's typed schema interface.
 *
 * @vee-validate/zod only supports Zod 3 and vee-validate 4 cannot consume
 * Standard Schema directly, so this covers the parts the forms rely on:
 * validation errors per field and initial values taken from `.default()`.
 */

// Turns "segments.0.start" into vee-validate's "segments[0].start".
const toFormPath = (path) =>
  path.reduce(
    (formPath, segment) =>
      typeof segment === 'number'
        ? `${formPath}[${segment}]`
        : formPath
          ? `${formPath}.${String(segment)}`
          : String(segment),
    ''
  );

const collectIssues = (issues, errors) => {
  issues.forEach((issue) => {
    if (issue.code === 'invalid_union') {
      issue.errors.forEach((unionIssues) => collectIssues(unionIssues, errors));
    }

    const path = toFormPath(issue.path);
    errors[path] ??= { path, errors: [] };
    errors[path].errors.push(issue.message);
  });
};

// Zod applies a default when the input is undefined, which is exactly how to read it back.
const defaultsOf = (objectSchema) =>
  Object.fromEntries(
    Object.entries(objectSchema.shape)
      .map(([key, fieldSchema]) => [key, fieldSchema.safeParse(undefined)])
      .filter(([, result]) => result.success && result.data !== undefined)
      .map(([key, result]) => [key, result.data])
  );

export function toTypedSchema(objectSchema) {
  return {
    __type: 'VVTypedSchema',

    async parse(values) {
      const result = await objectSchema.safeParseAsync(values);

      if (result.success) {
        return { value: result.data, errors: [] };
      }

      const errors = {};
      collectIssues(result.error.issues, errors);

      return { errors: Object.values(errors) };
    },

    cast(values) {
      return { ...defaultsOf(objectSchema), ...(values ?? {}) };
    },

    describe(path) {
      const fieldSchema = path ? objectSchema.shape[path] : objectSchema;

      if (!fieldSchema) {
        return { required: false, exists: false };
      }

      return {
        required: !fieldSchema.safeParse(undefined).success,
        exists: true,
      };
    },
  };
}
